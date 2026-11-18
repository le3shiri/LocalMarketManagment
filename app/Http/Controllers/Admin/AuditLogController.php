<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditChain;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditChain $chain): View
    {
        $filters = $request->validate([
            'user' => ['nullable', 'integer'],
            'action' => ['nullable', Rule::in(array_keys(AuditLog::ACTIONS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sensibles' => ['nullable', 'boolean'],
        ]);

        $logs = AuditLog::with('user')
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['sensibles'] ?? false, fn ($q) => $q->sensitive())
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'users' => User::whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::ACTIONS,
            'sensitiveOnly' => (bool) ($filters['sensibles'] ?? false),
            'integrity' => $chain->verify(),
        ]);
    }
}
