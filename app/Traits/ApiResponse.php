<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Retourner une réponse de succès
     */
    protected function successResponse($data = null, $message = 'Opération réussie', $status = 200)
    {
        $response = [
            'succes' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['donnees'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse d'erreur
     */
    protected function errorResponse($message = 'Une erreur est survenue', $status = 400, $errors = null)
    {
        $response = [
            'succes' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['erreurs'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Retourner une réponse de validation échouée
     */
    protected function validationErrorResponse($validator)
    {
        return $this->errorResponse('Données invalides', 422, $validator->errors());
    }
}