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
           
            'peso' => 'required|integer',
            'hemoglobina' => 'required|integer',
        ];
    }

    protected function messages(): array
    {
        return [
            'peso.required' => 'Peso é obrigatório',
            'hemoglobina.required' => 'Hemoglobina é obrigatória',
            'peso.integer' => 'Peso deve ser um número inteiro',
            'hemoglobina.integer' => 'Hemoglobina deve ser um número inteiro',

        ];
    }
}
