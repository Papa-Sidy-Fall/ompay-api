<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
            'destinataire_telephone' => 'required|string|exists:users,telephone',
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
            'destinataire_telephone.required' => 'Le téléphone du destinataire est obligatoire',
            'destinataire_telephone.string' => 'Le téléphone du destinataire doit être une chaîne',
            'destinataire_telephone.exists' => 'Le destinataire n\'existe pas',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant doit être supérieur à 0',
            'description.string' => 'La description doit être une chaîne de caractères',
            'description.max' => 'La description ne peut pas dépasser 255 caractères',
        ];
    }
}
