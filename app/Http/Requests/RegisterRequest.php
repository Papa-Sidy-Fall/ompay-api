<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users|regex:/^\+?[1-9]\d{1,14}$/',
            'pin' => 'required|string|min:4|max:6',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'nom.string' => 'Le nom doit être une chaîne de caractères',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères',
            'telephone.required' => 'Le téléphone est obligatoire',
            'telephone.string' => 'Le téléphone doit être une chaîne de caractères',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'telephone.regex' => 'Le format du numéro de téléphone est invalide',
            'pin.required' => 'Le PIN est obligatoire',
            'pin.string' => 'Le PIN doit être une chaîne de caractères',
            'pin.min' => 'Le PIN doit contenir au moins 4 caractères',
            'pin.max' => 'Le PIN ne peut pas dépasser 6 caractères',
        ];
    }
}
