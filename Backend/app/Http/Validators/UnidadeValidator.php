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
            'capacidade_diaria' => 'required|integer',
            'cep' => 'required|max:9|formato_cep',
            'logradouro' => 'max:120',
            'numero' => 'max:15',
            'complemento' => 'max:60',
            'bairro' => 'max:80',
            'cidade' => 'max:80',
            'uf' => 'max:2|in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO',
            'endereco_origem' => 'in:api,cache,manual,nao_resolvido'
        ];
    }

    protected function messages(): array
    {
        return [
            'nome.required' => 'Nome é obrigatório',
            'capacidade_diaria.required' => 'Capacidade diária é obrigatória',
            'capacidade_diaria.integer' => 'Capacidade diária deve ser apenas número inteiro',
            'cep.max' => 'max de caractéres:8',
            'cep.required' => 'CEP é obrigatório',
            'cep.formato_cep' => 'Cep inválido',
            'logradouro.max' => 'max de caractéres:120',
            'numero.max' => 'max de caractéres:15',
            'complemento.max' => 'max de caractéres:60',
            'bairro.max' => 'max de caractéres:80',
            'cidade.max' => 'max de caractéres:80',
            'uf.max' => 'max de caractéres:2',
            'uf.in' => 'UF fora do escopo: AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO',
            'endereco_origem.in' => 'Endereço de origem fora do escopo: api,cache,manual,nao_resolvido'
        ];
    }
}
