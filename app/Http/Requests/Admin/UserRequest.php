<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s]{9,20}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'phone' => 'teléfono', 'address' => 'dirección', 'role_id' => 'rol', 'password' => 'contraseña'];
    }

    /** An admin must not lock themselves out by editing their own account. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->route('user')?->is($this->user())) {
                return;
            }

            if ((int) $this->input('role_id') !== Role::idFor(Role::ADMIN)) {
                $validator->errors()->add('role_id', 'No puedes quitarte el rol de administrador.');
            }

            if (! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'No puedes desactivar tu propia cuenta.');
            }
        }];
    }
}
