<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Backups for the administrator: list, create on demand and download. Restoring replaces
 * all data, so it is deliberately only available from the command line (docs/BACKUP.md).
 */
class BackupController extends Controller
{
    public function index(BackupService $backups): View
    {
        return view('admin.backups.index', [
            'backups' => $backups->list(),
            'keep' => (int) config('backup.keep'),
            'service' => $backups,
        ]);
    }

    public function store(Request $request, BackupService $backups): RedirectResponse
    {
        try {
            $name = $backups->create($request->user());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'No se ha podido crear la copia: '.$e->getMessage());
        }

        return back()->with('status', "Copia {$name} creada.");
    }

    public function download(string $name, BackupService $backups): BinaryFileResponse
    {
        try {
            $path = $backups->existing($name);
        } catch (RuntimeException) {
            abort(404);
        }

        return response()->download($path, $name, ['Content-Type' => 'application/gzip']);
    }
}
