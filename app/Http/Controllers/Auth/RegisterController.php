<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s]{9,20}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['name' => 'nombre', 'phone' => 'teléfono', 'password' => 'contraseña']);

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('home')->with('status', '¡Bienvenido/a a LocalMarket!');
    }
}
