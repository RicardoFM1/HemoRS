<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoacaoValidatorCancela
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
            'motivo_da_recusa' => 'max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status fora do escopo: agendada, triagem, cancelada, coletada, recusada',

        ];
    }
}
