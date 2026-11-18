<?php

namespace Database\Seeders;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\UnitType;
use App\Enums\WriteOffType;
use App\Exceptions\CartException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\ExpiryService;
use App\Services\OrderService;
use App\Services\OrderWorkflow;
use App\Services\RefundService;
use App\Services\SaleService;
use App\Services\StockService;
use App\Services\WriteOffService;
use App\Support\SaleTotals;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Six weeks of believable shop activity for demos: weekly goods receipts with batches, daily
 * till tickets (busier at the weekend), web / phone / WhatsApp orders in every state,
 * returns, write-offs and batches about to expire.
 *
 * Everything goes through the real services, so stock, batches, payments, notifications and
 * the audit hash chain are exactly what the application would have produced. A fixed random
 * seed makes the data reproducible.
 *
 *   php artisan migrate:fresh --seed                       (local: includes this seeder)
 *   php artisan db:seed --class=DemoActivitySeeder         (on top of an existing catalogue)
 */
class DemoActivitySeeder extends Seeder
{
    /** Weeks of history to generate (tests use 1). */
    public int $weeks = 6;

    private const CUSTOMER_NAMES = [
        'Mireia Puig', 'Jordi Serra', 'Laia Font', 'Pau Vidal', 'Núria Soler', 'Oriol Costa', 'Carme Roca', 'Albert Mas',
        'Montse Pujol', 'Xavier Ferrer', 'Anna Riera', 'David Martí', 'Elena Sala', 'Joan Bosch', 'Rosa Vila', 'Marc Prat',
    ];

    private const WHATSAPP_MESSAGES = [
        'Hola! Para mañana quiero %s, gracias 😊',
        'Buenos días, ¿me preparáis %s para recoger por la tarde?',
        'Hola, soy yo otra vez. Necesito %s. ¿Se puede?',
    ];

    private User $cashier;

    private User $warehouse;

    private User $admin;

    /** @var Collection<int, User> */
    private Collection $customers;

    /** @var Collection<int, Product> */
    private Collection $products;

    public function __construct(
        private readonly StockService $stock,
        private readonly SaleService $sales,
        private readonly OrderService $orders,
        private readonly OrderWorkflow $workflow,
        private readonly RefundService $refunds,
        private readonly WriteOffService $writeOffs,
        private readonly ExpiryService $expiry,
    ) {}

    public function run(): void
    {
        if (Sale::exists() || Order::exists()) {
            $this->command?->warn('Ya hay ventas o pedidos: no se generan datos de demostración.');

            return;
        }

        mt_srand(2026);
        config(['mail.default' => 'array']); // don't fill the log with demo e-mails
        $realNow = CarbonImmutable::now();

        try {
            $this->prepareActors();
            $start = $realNow->startOfDay()->subWeeks($this->weeks);

            for ($day = $start; $day->lt($realNow->startOfDay()); $day = $day->addDay()) {
                $this->simulateDay($day, $realNow);
            }

            $this->simulateToday($realNow);
            $this->at($realNow);
            $this->expiry->blockExpired($this->warehouse);
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }

        $this->command?->info(sprintf('Demo: %d tickets, %d pedidos, %d devoluciones.', Sale::count(), Order::count(), \App\Models\Refund::count()));
    }

    private function prepareActors(): void
    {
        $this->cashier = User::firstWhere('email', 'caja@localmarket.test') ?? User::factory()->withRole(Role::CASHIER)->create(['name' => 'Laura Caja']);
        $this->warehouse = User::firstWhere('email', 'almacen@localmarket.test') ?? User::factory()->withRole(Role::WAREHOUSE)->create(['name' => 'Jordi Almacén']);
        $this->admin = User::firstWhere('email', 'admin@localmarket.test') ?? User::factory()->admin()->create();

        $this->customers = collect(self::CUSTOMER_NAMES)->map(fn (string $name, int $i) => User::firstOrCreate(
            ['email' => 'demo'.($i + 1).'@localmarket.test'],
            ['name' => $name, 'phone' => sprintf('6%02d %03d %03d', 10 + $i, 100 + $i * 7, 200 + $i * 13), 'password' => 'password', 'role_id' => Role::idFor(Role::CUSTOMER)],
        ));

        if ($demo = User::firstWhere('email', 'cliente@localmarket.test')) {
            // Listed several times so "Mis pedidos" / "Repetir pedido" always have history in the demo.
            $this->customers->push($demo, $demo, $demo, $demo);
        }

        // Explicit order: the same seed must give the same data on SQLite and MySQL.
        $this->products = Product::active()->with('stock')->orderBy('id')->get();
    }

    private function simulateDay(CarbonImmutable $day, CarbonImmutable $realNow): void
    {
        if ($day->isSunday()) {
            return; // closed
        }

        if ($day->isMonday() || $day->eq($realNow->startOfDay()->subWeeks($this->weeks))) {
            $this->weeklyExpiryRound($day->setTime(7, 45));
            $this->receiveGoods($day->setTime(8, 0));
        }

        // Busier on Friday and Saturday.
        $tickets = match (true) {
            $day->isSaturday() => mt_rand(16, 22),
            $day->isFriday() => mt_rand(12, 16),
            default => mt_rand(7, 12),
        };

        $isRecent = $day->diffInDays($realNow->startOfDay()) <= 1;
        // The last day leaves extra open orders for the staff panel, whatever time the seeder runs.
        $orders = mt_rand(2, $day->isSaturday() ? 6 : 4) + ($isRecent ? 4 : 0);

        for ($i = 0; $i < $orders; $i++) {
            $this->placeOrder($day->setTime(mt_rand(8, 11), mt_rand(0, 59)), finish: ! $isRecent);
        }

        $hours = collect(range(1, $tickets))->map(fn () => $day->setTime(mt_rand(9, 20), mt_rand(0, 59)))->sort();
        foreach ($hours as $when) {
            $this->tillSale($when);
        }

        if (mt_rand(1, 4) === 1) {
            $this->refundRecentTicket($day->setTime(18, 30));
        }

        if (mt_rand(1, 3) === 1) {
            $this->writeOff($day->setTime(20, 15));
        }
    }

    /** Today: a morning's worth of activity and open orders for the staff panel. */
    private function simulateToday(CarbonImmutable $now): void
    {
        $opening = $now->startOfDay()->setTime(9, 0);

        if ($now->lt($opening)) {
            return;
        }

        for ($i = 0; $i < 4; $i++) {
            $this->placeOrder(CarbonImmutable::createFromTimestamp(mt_rand($opening->timestamp, $now->timestamp)), finish: false);
        }

        for ($i = 0; $i < 6; $i++) {
            $this->tillSale(CarbonImmutable::createFromTimestamp(mt_rand($opening->timestamp, $now->timestamp)));
        }
    }

    /** Before Monday's delivery the warehouse blocks expired batches and takes them off the shelf. */
    private function weeklyExpiryRound(CarbonImmutable $when): void
    {
        $this->at($when);

        foreach ($this->expiry->blockExpired($this->warehouse) as $batch) {
            $this->expiry->confirmWithdrawal($batch->refresh(), $this->warehouse);
        }
    }

    /** Monday delivery: top every product up, with a batch and (for fresh food) an expiry date. */
    private function receiveGoods(CarbonImmutable $when): void
    {
        $this->at($when);

        foreach ($this->products as $product) {
            $weighted = $product->unit_type === UnitType::Weight;
            [$store, $online] = $weighted ? [mt_rand(12, 18), mt_rand(4, 7)] : [mt_rand(20, 35), mt_rand(6, 12)];

            $this->stock->registerEntry($product, $store, $online, $this->warehouse,
                'ALB-'.$when->format('ymd').'-'.$product->id, null,
                lotCode: 'L'.$when->format('ymd').'-'.$product->sku,
                expiresAt: $product->is_perishable ? $when->addDays(mt_rand(5, 12))->toDateString() : null,
            );
        }
    }

    private function tillSale(CarbonImmutable $when): void
    {
        $this->at($when);

        $lines = $this->pickProducts(mt_rand(1, 5))->map(function (Product $product) {
            $product->stock->refresh();
            $quantity = $product->unit_type === UnitType::Weight
                ? round(mt_rand(250, 1600) / 1000, 3)   // read from the scale
                : (float) mt_rand(1, 3);

            return $product->stock->store_qty >= $quantity ? ['product' => $product, 'quantity' => $quantity] : null;
        })->filter()->values();

        if ($lines->isEmpty()) {
            return;
        }

        $totals = SaleTotals::for($lines);
        $cash = mt_rand(1, 100) <= 60;
        $tendered = $cash ? $this->tenderFor($totals->total) : null;

        try {
            $this->sales->complete($totals, [
                'method' => $cash ? PaymentMethod::Cash : PaymentMethod::Card,
                'tendered' => $tendered,
                'change' => $cash ? round($tendered - $totals->total, 2) : null,
                'reference' => $cash ? null : strtoupper(substr(md5((string) mt_rand()), 0, 6)),
            ], $this->cashier);
        } catch (InvalidArgumentException) {
            // Shelf ran out between picks: skip the ticket, like a real customer would.
        }
    }

    private function placeOrder(CarbonImmutable $when, bool $finish): void
    {
        $this->at($when);

        // mt_rand (seeded), not Collection::random() which uses random_int and ignores the seed.
        $customer = $this->customers[mt_rand(0, $this->customers->count() - 1)];
        $channel = ['web', 'web', 'web', 'web', 'web', 'whatsapp', 'whatsapp', 'whatsapp', 'phone', 'phone'][mt_rand(0, 9)];
        $delivery = mt_rand(1, 100) <= 30;
        $slotDay = mt_rand(0, 1) ? $when : $when->addDay();
        if ($slotDay->isSunday()) {
            $slotDay = $slotDay->addDay();
        }

        $lines = $this->pickProducts(mt_rand(1, 4))
            ->mapWithKeys(fn (Product $p) => [$p->id => $p->unit_type === UnitType::Weight ? mt_rand(2, 6) * 0.25 : (float) mt_rand(1, 3)])
            ->filter(fn ($qty, $id) => $this->products->firstWhere('id', $id)->stock->refresh()->available_online >= $qty);

        if ($lines->isEmpty()) {
            return;
        }

        $data = [
            'fulfillment' => $delivery ? Fulfillment::Delivery->value : Fulfillment::Pickup->value,
            'payment_method' => mt_rand(0, 1) ? 'cash' : 'card',
            'contact_name' => $customer->name,
            'contact_phone' => $customer->phone,
            'delivery_address' => $delivery ? 'Carrer de Sants '.mt_rand(1, 300).', Barcelona' : null,
            'slot_date' => $slotDay->toDateString(),
            'slot' => ['17:00-18:00', '18:00-19:00', '19:00-20:00'][mt_rand(0, 2)],
        ];

        try {
            if ($channel === 'web') {
                $customer->cartItems()->delete();
                foreach ($lines as $productId => $quantity) {
                    $customer->cartItems()->create(['product_id' => $productId, 'quantity' => $quantity]);
                }
                $order = $this->orders->placeFromCart($customer, $data);
            } else {
                if ($channel === 'whatsapp') {
                    $names = $lines->keys()->map(fn ($id) => mb_strtolower($this->products->firstWhere('id', $id)->name))->take(2)->implode(' y ');
                    $data['source_message'] = sprintf(self::WHATSAPP_MESSAGES[mt_rand(0, 2)], $names);
                    $data['source_reference'] = 'Chat '.$customer->name.' '.$when->format('H:i');
                }
                $order = $this->orders->placeForStaff($channel, $lines->all(), $data, $this->cashier);
            }
        } catch (CartException) {
            return; // slot full or stock changed: the customer would have chosen another
        }

        if (! $finish) {
            // Leave recent orders spread over the open states for the staff panel.
            $target = [OrderStatus::Pending, OrderStatus::Pending, OrderStatus::Preparing, OrderStatus::Ready][mt_rand(0, 3)];
            $this->moveTo($order, $target, $when->addMinutes(30));

            return;
        }

        if (mt_rand(1, 100) <= 8) {
            $this->at($when->addHours(2));
            $this->workflow->cancel($order, $this->cashier, ['El cliente ha llamado para anularlo', 'Producto no disponible y no acepta sustituto', 'Pedido duplicado'][mt_rand(0, 2)]);

            return;
        }

        $this->moveTo($order, OrderStatus::Delivered, $slotDay->setTime(mt_rand(17, 19), mt_rand(0, 59)));
    }

    private function moveTo(Order $order, OrderStatus $target, CarbonImmutable $deliveredAt): void
    {
        $steps = [OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Delivered];
        $time = CarbonImmutable::parse($order->created_at)->addMinutes(20);

        foreach ($steps as $step) {
            if (array_search($step, $steps, true) > array_search($target, $steps, true) || $target === OrderStatus::Pending) {
                return;
            }

            $time = $step === OrderStatus::Delivered ? max($deliveredAt, $time->addMinutes(10)) : $time->addMinutes(mt_rand(15, 90));
            $this->at($time);

            // Real weights differ a little from what was ordered.
            $weights = $step === OrderStatus::Delivered
                ? $order->items()->where('unit_type', 'weight')->get()->mapWithKeys(fn ($i) => [$i->id => round($i->quantity * mt_rand(92, 108) / 100, 3)])->all()
                : [];

            try {
                $order = $this->workflow->advance($order, $step, $this->cashier, weights: $weights);
            } catch (\App\Exceptions\OrderTransitionException) {
                $order = $this->workflow->advance($order, $step, $this->cashier); // extra weight not available: deliver as ordered
            }
        }
    }

    private function refundRecentTicket(CarbonImmutable $when): void
    {
        // Pick with mt_rand (not SQL RANDOM()) so the data is reproducible on any database.
        $candidates = Sale::where('created_at', '>=', $when->subDays(5))->where('created_at', '<=', $when)->orderBy('id')->pluck('id');
        $sale = $candidates->isEmpty() ? null : Sale::with('items')->find($candidates[mt_rand(0, $candidates->count() - 1)]);
        $item = $sale?->items->first();

        if (! $item || $item->refundableQuantity() <= 0) {
            return;
        }

        $this->at($when);
        $quantity = $item->unit_type === UnitType::Weight ? $item->quantity : 1.0;

        $this->refunds->refund($sale, [$item->id => $quantity],
            ['Producto en mal estado', 'El cliente se ha equivocado de producto', 'Envase dañado'][mt_rand(0, 2)], $this->admin);
    }

    private function writeOff(CarbonImmutable $when): void
    {
        $this->at($when);
        $product = $this->pickProducts(1)->first();
        $product->stock->refresh();
        $quantity = $product->unit_type === UnitType::Weight ? 0.5 : 1.0;

        if ($product->stock->store_qty < $quantity) {
            return;
        }

        $this->writeOffs->record($product, $quantity, 'store', mt_rand(1, 4) === 1 ? WriteOffType::Recalled : WriteOffType::Damaged,
            $this->warehouse, notes: ['Caja aplastada en la descarga', 'Golpeado en el expositor', 'Retirado por el proveedor'][mt_rand(0, 2)]);
    }

    /** @return Collection<int, Product> distinct random products, popular ones more often */
    private function pickProducts(int $count): Collection
    {
        // Lower ids (fruit, meat, dairy in the demo catalogue) sell more: weight by position.
        $weighted = $this->products->flatMap(fn (Product $p, int $i) => array_fill(0, max(1, 6 - intdiv($i, 4)), $p));

        return collect(range(1, $count))->map(fn () => $weighted[mt_rand(0, $weighted->count() - 1)])->unique('id')->values();
    }

    private function tenderFor(float $total): float
    {
        foreach ([5, 10, 20, 50] as $note) {
            if ($total <= $note && mt_rand(0, 2)) {
                return (float) $note;
            }
        }

        return ceil($total);
    }

    private function at(CarbonImmutable $moment): void
    {
        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);
    }
}
