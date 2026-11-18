<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('role')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->when($request->integer('role'), fn ($q, $role) => $q->where('role_id', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true]), 'roles' => Role::orderBy('id')->get()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->except('is_active'));
        $user->is_active = $request->boolean('is_active');
        $user->save();

        return redirect()->route('admin.users.index')->with('status', "Usuario {$user->name} creado.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'roles' => Role::orderBy('id')->get()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except(['password', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->fill($data);
        $user->is_active = $request->boolean('is_active');
        $user->save();

        return redirect()->route('admin.users.index')->with('status', "Usuario {$user->name} actualizado.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status', $user->is_active ? "Cuenta de {$user->name} activada." : "Cuenta de {$user->name} desactivada.");
    }
}
