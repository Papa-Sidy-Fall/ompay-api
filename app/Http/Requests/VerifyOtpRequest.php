<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'telephone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'code' => 'required|string|size:4',
            'type' => 'required|in:inscription,connexion,transaction',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le téléphone est obligatoire',
            'telephone.string' => 'Le téléphone doit être une chaîne de caractères',
            'telephone.regex' => 'Le format du numéro de téléphone est invalide',
            'code.required' => 'Le code OTP est obligatoire',
            'code.string' => 'Le code OTP doit être une chaîne de caractères',
            'code.size' => 'Le code OTP doit contenir exactement 4 caractères',
            'type.required' => 'Le type d\'OTP est obligatoire',
            'type.in' => 'Le type d\'OTP doit être inscription, connexion ou transaction',
        ];
    }
}
