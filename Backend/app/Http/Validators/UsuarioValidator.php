<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UsuarioValidator {
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
            'email' => 'required|email|max:255',
            'senha' => 'required|string|max:255',
            'perfil' => 'required|in:recepcao,enfermagem,gestor',
            'status' => 'required|in:ativo,inativo'
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'Nome é obrigatório',
            'email.required' => 'Email é obrigatório',
            'email.email' => 'Email inválido',
            'senha.required' => 'Senha é obrigatória',
            'perfil.required' => 'Perfil é obrigatório',
            'perfil.in' => 'Perfil fora do escopo: recepcao, enfermagem e gestor',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status fora do escopo: ativo, inativo'
        ];
    }
}