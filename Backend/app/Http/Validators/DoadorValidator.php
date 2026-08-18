<?php

namespace App\Http\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DoadorValidator
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
            'cpf' => 'required|cpf',
            'sexo' => 'required|in:masculino,feminino,outros',
            'tipo_sanguineo' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'telefone' => 'required|max:20',
            'data_de_nascimento' => 'required',
            'email' => 'required|email|max:255',
            'status' => 'required|in:ativo,inativo'
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'Nome é obrigatório',
            'email.required' => 'Email é obrigatório',
            'email.email' => 'Email inválido',
            'cpf.required' => 'CPF é obrigatório',
            'cpf.cpf' => 'CPF inválido',
            'data_de_nascimento.required' => 'Data de nascimento é obrigatório',
            'sexo.required' => 'Sexo é obrigatório',
            'sexo.in' => 'Sexo fora do escopo: masculino, feminino e outros',
            'tipo_sanguineo.required' => 'Tipo sanguíneo é obrigatório',
            'tipo_sanguineo.in' => 'Tipo sanguíneo fora do escopo: A+, A-, B+, B-, AB+, AB-, O+, O-',
            'telefone' => 'Telefone é obrigatório',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status fora do escopo: ativo ou inativo'
        ];
    }
}
