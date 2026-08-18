<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoacaoValidatorTriagem
{
    public function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function rules(): array
    {
        return [
            'status' => 'required|in:agendada,triagem,cancelada,coletada,recusada',
            'peso' => 'required|integer',
            'hemoglobina' => 'required|integer',
        ];
    }

    protected function messages(): array
    {
        return [
            'status.required' => 'Status é obrigatório',
            'peso.required' => 'Peso é obrigatório',
            'hemoglobina.required' => 'Hemoglobina é obrigatória',
            'status.in' => 'Status fora do escopo: agendada, triagem, cancelada, coletada, recusada',
            'peso.integer' => 'Peso deve ser um número inteiro',
            'hemoglobina.integer' => 'Hemoglobina deve ser um número inteiro',

        ];
    }
}
