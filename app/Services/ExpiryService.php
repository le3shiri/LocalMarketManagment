<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\StockBatch;
use App\Models\User;
use App\Notifications\ExpiryAlertNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

class ExpiryService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Block every batch whose expiry date has passed. Runs daily from the scheduler and
     * can be triggered from the expiry screen.
     *
     * @return Collection<int, StockBatch> batches blocked in this run
     */
    public function blockExpired(?User $user = null): Collection
    {
        $expired = StockBatch::with('product')
            ->where('status', StockBatch::ACTIVE)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', today())
            ->orderBy('expires_at')
            ->get();

        foreach ($expired as $batch) {
            $blocked = $this->stock->expireBatch($batch, $user);
            AuditLog::record('stock', "Lote {$batch->lot_code} de «{$batch->product->name}» caducado: {$batch->product->formatQuantity($blocked)} retirados de la venta", $batch->product, user: $user);
        }

        return $expired;
    }

    /**
     * Notify warehouse staff about batches expiring within the warning window. A batch is
     * announced at most once per day.
     *
     * @return int batches announced
     */
    public function sendAlerts(): int
    {
        $batches = StockBatch::with('product')
            ->where('status', StockBatch::ACTIVE)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', today())
            ->whereDate('expires_at', '<=', today()->addDays((int) config('shop.expiry_warning_days')))
            ->where(fn ($q) => $q->whereNull('alerted_on')->orWhereDate('alerted_on', '<', today()))
            ->orderBy('expires_at')
            ->get();

        if ($batches->isEmpty()) {
            return 0;
        }

        $recipients = User::where('is_active', true)
            ->whereHas('role.permissions', fn ($q) => $q->where('name', 'stock.exit'))
            ->get();

        Notification::send($recipients, new ExpiryAlertNotification($batches));

        StockBatch::whereKey($batches->modelKeys())->update(['alerted_on' => today()]);

        return $batches->count();
    }

    /** Staff confirm the expired units were physically taken off the shelf. */
    public function confirmWithdrawal(StockBatch $batch, User $user): void
    {
        $batch->update(['status' => StockBatch::WITHDRAWN, 'quantity_remaining' => 0]);

        AuditLog::record('stock', "Retirada física del lote caducado {$batch->lot_code} de «{$batch->product->name}»", $batch->product, user: $user);
    }
}
