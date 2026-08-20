<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoacaoValidator
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
            'doador_id' => 'required|integer',
            'unidade_id' => 'required|integer',
            'usuario_id' => 'required|integer',
            'status' => 'in:agendada,triagem,cancelada,coletada,recusada',
            'peso' => 'integer',
            'hemoglobina' => 'integer',
            'motivo_da_recusa' => 'max:255',
            'volume_coletado' => 'integer',
            'data_e_hora_agendada' => 'required'
        ];
    }

    protected function messages(): array
    {
        return [
            'doador_id.required' => 'Referência do doador é obrigatório',
            'doador_id.integer' => 'Referência do doador deve ser um número inteiro',
            'unidade_id.required' => 'Referência da unidade é obrigatória',
            'unidade_id.integer' => 'Referência da unidade deve ser um número inteiro',
            'usuario_id.required' => 'Referência do usuário é obrigatório',
            'usuario_id.integer' => 'Referência do usuário deve ser um número inteiro',
            'status.in' => 'Status fora do escopo: agendada, triagem, cancelada, coletada, recusada',
            'peso.integer' => 'Peso deve ser um número inteiro',
            'hemoglobina.integer' => 'Hemoglobina deve ser um número inteiro',
            'data_e_hora_agendada.required' => 'Data e hora para agendamento é obrigatório'
        ];
    }
}
