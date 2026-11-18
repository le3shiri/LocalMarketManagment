<?php

namespace App\Notifications;

use App\Models\StockBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Daily notice to warehouse staff: batches that expire soon and should leave first.
 */
class ExpiryAlertNotification extends Notification
{
    use Queueable;

    /** @param  Collection<int, StockBatch>  $batches */
    public function __construct(public readonly Collection $batches) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function title(): string
    {
        $count = $this->batches->count();

        return $count === 1 ? '1 lote caduca pronto' : "{$count} lotes caducan pronto";
    }

    /** @return list<string> */
    public function lines(): array
    {
        return $this->batches->map(function (StockBatch $batch) {
            $when = match ($batch->days_left) {
                0 => 'hoy',
                1 => 'mañana',
                default => "en {$batch->days_left} días",
            };

            return "{$batch->product->name} · lote {$batch->lot_code} · {$batch->product->formatQuantity($batch->quantity_remaining)} · caduca {$when}";
        })->all();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('LocalMarket · '.$this->title())
            ->greeting("Hola, {$notifiable->name}")
            ->line('Estos lotes deberían salir primero para evitar mermas:');

        foreach ($this->lines() as $line) {
            $mail->line('· '.$line);
        }

        return $mail->action('Ver caducidades', route('warehouse.expiry.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => implode(' · ', array_slice($this->lines(), 0, 3)).($this->batches->count() > 3 ? '…' : ''),
            'url' => route('warehouse.expiry.index'),
            'batch_ids' => $this->batches->pluck('id')->all(),
        ];
    }
}
