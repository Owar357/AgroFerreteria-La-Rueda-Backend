<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class PasswordNoUsadaPorOtro implements ValidationRule
{
    public function __construct(private ?int $ignorarUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = User::query()->select('id', 'password');

        if ($this->ignorarUserId) {
            $query->where('id', '!=', $this->ignorarUserId);
        }

        foreach ($query->cursor() as $user) {
            if (Hash::check($value, $user->password)) {
                $fail('Esta contraseña no está disponible. Elige una diferente.');
                return;
            }
        }
    }
}