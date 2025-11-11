<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTransactionRequest extends FormRequest
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
            'transaction_id' => 'required|uuid',
            'code' => 'required|string|size:4',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'L\'ID de transaction est obligatoire',
            'transaction_id.uuid' => 'L\'ID de transaction doit être un UUID valide',
            'code.required' => 'Le code OTP est obligatoire',
            'code.string' => 'Le code OTP doit être une chaîne de caractères',
            'code.size' => 'Le code OTP doit contenir exactement 4 caractères',
        ];
    }
}
