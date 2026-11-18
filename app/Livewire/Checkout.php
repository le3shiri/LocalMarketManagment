<?php

namespace App\Livewire;

use App\Enums\Fulfillment;
use App\Exceptions\CartException;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\CartTotals;
use App\Support\Slots;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Checkout with "home delivery vs store pickup" selector (SUP-62 / SUP-63).
 */
#[Layout('components.layouts.app')]
#[Title('Tramitar pedido')]
class Checkout extends Component
{
    public string $fulfillment = 'pickup';

    public string $contact_name = '';

    public string $contact_phone = '';

    public string $delivery_address = '';

    public string $slot_date = '';

    public string $slot = '';

    public string $notes = '';

    public ?string $error = null;

    public function mount(CartService $cart, Slots $slots)
    {
        $user = auth()->user();

        if ($cart->count($user) === 0) {
            return $this->redirectRoute('cart.index');
        }

        $this->contact_name = $user->name;
        $this->contact_phone = (string) $user->phone;
        $this->delivery_address = (string) $user->address;
        $this->slot_date = $slots->days()->first()?->toDateString() ?? '';
    }

    public function updatedSlotDate(): void
    {
        $this->slot = '';
    }

    protected function rules(): array
    {
        $days = app(Slots::class)->days()->map->toDateString()->all();

        return [
            'fulfillment' => ['required', Rule::enum(Fulfillment::class)],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'regex:/^[0-9+\s]{9,20}$/'],
            'delivery_address' => [Rule::requiredIf($this->fulfillment === Fulfillment::Delivery->value), 'nullable', 'string', 'max:255'],
            'slot_date' => ['required', Rule::in($days)],
            'slot' => ['required', Rule::in(config('shop.slots'))],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'contact_name' => 'nombre', 'contact_phone' => 'teléfono', 'delivery_address' => 'dirección de entrega',
            'slot_date' => 'día', 'slot' => 'franja horaria', 'notes' => 'observaciones',
        ];
    }

    public function placeOrder(OrderService $orders)
    {
        $data = $this->validate();

        try {
            $order = $orders->placeFromCart(auth()->user(), $data);
        } catch (CartException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        session()->flash('status', "¡Pedido {$order->code} confirmado! Te avisaremos cuando esté listo.");

        return $this->redirectRoute('orders.show', $order);
    }

    public function render(CartService $cart, Slots $slots): View
    {
        $items = $cart->items(auth()->user());
        $day = $this->slot_date ? CarbonImmutable::parse($this->slot_date) : null;

        return view('livewire.checkout', [
            'items' => $items,
            'totals' => CartTotals::for($items, Fulfillment::tryFrom($this->fulfillment) ?? Fulfillment::Pickup),
            'days' => $slots->days(),
            'availableSlots' => $day ? $slots->available($day) : [],
            'fulfillments' => Fulfillment::cases(),
        ]);
    }
}
