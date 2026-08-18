<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UnidadeValidator
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
            'nome' => 'required|string|max:150',
            'cidade' => 'required|string|max:150',
            'capacidade_diaria' => 'required|integer'
            
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'Nome é obrigatório',
            'cidade.required' => 'Cidade é obrigatória',
            'capacidade_diaria.required' => 'Capacidade diária é obrigatória',
            'capacidade_diaria.integer' => 'Capacidade diária deve ser apenas número inteiro'
        ];
    }
}
