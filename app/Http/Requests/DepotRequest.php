<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepotRequest extends FormRequest
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
            'distributeur_uuid' => 'nullable|uuid|exists:distributeurs,uuid',
            'montant' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'distributeur_uuid.required' => 'L\'UUID du distributeur est obligatoire',
            'distributeur_uuid.uuid' => 'L\'UUID du distributeur doit être valide',
            'distributeur_uuid.exists' => 'Le distributeur n\'existe pas',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant doit être supérieur à 0',
        ];
    }
}
