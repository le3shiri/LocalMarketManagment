<?php

namespace App\Notifications;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Customer notice for the status changes listed in section 6 of the proposal:
 * confirmation (with order number), ready, and cancellation.
 */
class OrderStatusNotification extends Notification
{
    use Queueable;

    /** Statuses the customer is told about. */
    public const NOTIFIED = [OrderStatus::Pending, OrderStatus::Ready, OrderStatus::Cancelled];

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $status,
        public readonly ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function title(): string
    {
        return match ($this->status) {
            OrderStatus::Pending => "Pedido {$this->order->code} confirmado",
            OrderStatus::Ready => $this->order->fulfillment === Fulfillment::Pickup
                ? "Tu pedido {$this->order->code} está listo para recoger"
                : "Tu pedido {$this->order->code} está listo y sale en reparto",
            OrderStatus::Cancelled => "Pedido {$this->order->code} cancelado",
            default => "Pedido {$this->order->code}: {$this->status->label()}",
        };
    }

    public function body(): string
    {
        $slot = $this->order->slot_date->locale('es')->isoFormat('dddd D [de] MMMM').", {$this->order->slot}";

        return match ($this->status) {
            OrderStatus::Pending => "Hemos recibido tu pedido. {$this->order->fulfillment->label()}: {$slot}.",
            OrderStatus::Ready => $this->order->fulfillment === Fulfillment::Pickup
                ? "Puedes pasar a recogerlo {$slot}."
                : "Te lo llevamos {$slot}.",
            OrderStatus::Cancelled => 'Tu pedido se ha cancelado.'.($this->note ? " Motivo: {$this->note}." : ''),
            default => "Nuevo estado: {$this->status->label()}.",
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting("Hola, {$notifiable->name}")
            ->line($this->body())
            ->action('Ver mi pedido', route('orders.show', $this->order))
            ->salutation('LocalMarket · tu tienda de barrio');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_code' => $this->order->code,
            'status' => $this->status->value,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }
}
