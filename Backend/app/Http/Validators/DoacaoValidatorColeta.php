<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoacaoValidatorColeta
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
            'volume_coletado' => 'required|integer'
        ];
    }

    protected function messages(): array
    {
        return [
            'volume_coletado.required' => 'Volume coletado é obrigatório',
            'volume_coletado.integer' => 'Volume coletado deve ser um número inteiro'
        ];
    }
}
