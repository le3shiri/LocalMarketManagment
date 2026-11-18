<?php

namespace App\Livewire\Staff;

use App\Enums\Fulfillment;
use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Support\CartTotals;
use App\Support\Slots;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Order form for the shop staff: orders received by phone.
 */
#[Layout('components.layouts.app')]
class CreateOrder extends Component
{
    #[Url(as: 'canal')]
    public string $channel = 'phone';

    public string $contact_phone = '';

    public string $contact_name = '';

    public string $fulfillment = 'pickup';

    public string $delivery_address = '';

    public string $slot_date = '';

    public string $slot = '';

    public string $notes = '';

    public string $search = '';

    /** @var array<int, float> product_id => quantity */
    public array $lines = [];

    public ?string $error = null;

    public function mount(Slots $slots): void
    {
        abort_unless(in_array($this->channel, $this->channels(), true), 404);
        $this->slot_date = $slots->days()->first()?->toDateString() ?? '';
    }

    /** WhatsApp only: conversation reference and the customer's original message. */
    public string $source_reference = '';

    public string $source_message = '';

    /** Codes of open orders that look like the same request; staff must confirm to continue. */
    public array $duplicates = [];

    public bool $confirmDuplicate = false;

    /** Channels this form can register. */
    protected function channels(): array
    {
        return ['phone', 'whatsapp'];
    }

    /** Fill in the customer's details as soon as a known phone number is typed. */
    public function updatedContactPhone(): void
    {
        if ($customer = $this->customer) {
            $this->contact_name = $customer->name;
            $this->delivery_address = (string) ($customer->address ?? $this->delivery_address);
        }
    }

    public function updatedSlotDate(): void
    {
        $this->slot = '';
    }

    public function saveAnyway(OrderService $orders)
    {
        $this->confirmDuplicate = true;

        return $this->save($orders);
    }

    #[Computed]
    public function customer(): ?User
    {
        return User::findByPhone($this->contact_phone)?->fresh();
    }

    #[Computed]
    public function results(): Collection
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        return Product::active()->with('stock')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
            ->orderBy('name')->limit(8)->get();
    }

    public function addProduct(int $productId): void
    {
        $product = Product::active()->findOrFail($productId);
        $this->lines[$productId] = round(($this->lines[$productId] ?? 0) + $product->unit_type->step(), 3);
        $this->search = '';
    }

    public function setQuantity(int $productId, $quantity): void
    {
        $quantity = (float) str_replace(',', '.', (string) $quantity);

        if ($quantity <= 0) {
            unset($this->lines[$productId]);
        } else {
            $this->lines[$productId] = $quantity;
        }
    }

    public function removeLine(int $productId): void
    {
        unset($this->lines[$productId]);
    }

    protected function rules(): array
    {
        return [
            'channel' => ['required', Rule::in($this->channels())],
            'contact_phone' => ['required', 'string', 'regex:/^[0-9+\s]{9,20}$/'],
            'contact_name' => ['required', 'string', 'max:255'],
            'fulfillment' => ['required', Rule::enum(Fulfillment::class)],
            'delivery_address' => [Rule::requiredIf($this->fulfillment === Fulfillment::Delivery->value), 'nullable', 'string', 'max:255'],
            'slot_date' => ['required', Rule::in(app(Slots::class)->days()->map->toDateString()->all())],
            'slot' => ['required', Rule::in(config('shop.slots'))],
            'notes' => ['nullable', 'string', 'max:500'],
            'source_reference' => ['nullable', 'string', 'max:100'],
            'source_message' => [Rule::requiredIf($this->channel === 'whatsapp'), 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'contact_phone' => 'teléfono', 'contact_name' => 'nombre', 'delivery_address' => 'dirección de entrega',
            'slot_date' => 'día', 'slot' => 'franja horaria', 'notes' => 'observaciones',
            'source_reference' => 'referencia de la conversación', 'source_message' => 'mensaje original',
        ];
    }

    public function save(OrderService $orders)
    {
        $data = $this->validate();

        if ($this->channel !== 'whatsapp') {
            unset($data['source_reference'], $data['source_message']);
        }

        if (! $this->confirmDuplicate) {
            $this->duplicates = $orders->possibleDuplicates($data['contact_phone'], $data['slot_date'], $data['slot'])
                ->pluck('code')->all();

            if ($this->duplicates !== []) {
                return null; // the view asks the employee to confirm
            }
        }

        try {
            $order = $orders->placeForStaff($this->channel, $this->lines, $data, auth()->user());
        } catch (CartException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        session()->flash('status', "Pedido {$order->code} registrado ({$order->channelLabel()}).");

        return $this->redirectRoute('staff.orders.show', $order);
    }

    public function render(Slots $slots): View
    {
        $products = Product::with('stock')->whereIn('id', array_keys($this->lines))->get()->keyBy('id');
        $items = collect($this->lines)
            ->filter(fn ($q, $id) => isset($products[$id]))
            ->map(fn ($quantity, $id) => tap(new CartItem(['product_id' => $id, 'quantity' => $quantity]))->setRelation('product', $products[$id]));

        $day = $this->slot_date ? CarbonImmutable::parse($this->slot_date) : null;

        return view('livewire.staff.create-order', [
            'items' => $items,
            'totals' => CartTotals::for($items, Fulfillment::tryFrom($this->fulfillment) ?? Fulfillment::Pickup),
            'days' => $slots->days(),
            'availableSlots' => $day ? $slots->available($day) : [],
        ])->title($this->channel === 'whatsapp' ? 'Pedido por WhatsApp' : 'Pedido por teléfono');
    }
}
