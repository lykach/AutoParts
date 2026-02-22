<?php

namespace App\Http\Requests;

use App\Rules\UkrainianPhone;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', new UkrainianPhone],
            'password' => ['required', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ім\'я є обов\'язковим',
            'email.required' => 'Email є обов\'язковим',
            'email.unique' => 'Такий email вже зареєстрований',
            'password.required' => 'Пароль є обов\'язковим',
            'password.min' => 'Пароль повинен містити мінімум 8 символів',
            'password.confirmed' => 'Паролі не співпадають',
        ];
    }
}
