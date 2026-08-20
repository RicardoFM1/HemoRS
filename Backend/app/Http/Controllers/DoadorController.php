<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoadorValidator;
use App\Models\Doacao;
use App\Models\Doador;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class DoadorController extends Controller
{
    // Função para listar os doadores com filtros
    public function listarDoadores(Request $request)
    {
        $query = Doador::query()->with('doacao');

        $busca = trim((string) $request->input('busca', ''));
        $tipoSanguineo = $request->input('tipo_sanguineo');
        $status = $request->input('status');
        $unidadeId = $request->input('unidade_id');
        $dataInicio = $request->input('data_inicio', $request->input('periodo_inicio'));
        $dataFim = $request->input('data_fim', $request->input('periodo_fim'));
        $ordenar = $request->input('ordenar', 'id');
        $direcao = strtolower((string) $request->input('direcao', 'desc'));
        $porPagina = (int) $request->input('por_pagina', 15);
        $porPagina = min(max($porPagina, 1), 100);

        $camposPermitidos = ['id', 'nome', 'cpf', 'data_de_nascimento', 'sexo', 'tipo_sanguineo', 'status'];
        if (!in_array($ordenar, $camposPermitidos, true)) {
            $ordenar = 'id';
        }

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        if (!empty($tipoSanguineo)) {
            $query->where('tipo_sanguineo', $tipoSanguineo);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($unidadeId)) {
            $query->whereHas('doacao', function ($q) use ($unidadeId) {
                $q->where('unidade_id', $unidadeId);
            });
        }

        if (!empty($dataInicio) || !empty($dataFim)) {
            $inicio = !empty($dataInicio) ? $dataInicio : '1900-01-01';
            $fim = !empty($dataFim) ? $dataFim : date('Y-m-d');
            $query->whereBetween('data_de_nascimento', [$inicio, $fim]);
        }

        $paginate = $query->orderBy($ordenar, $direcao === 'asc' ? 'asc' : 'desc')
            ->paginate($porPagina);

        return response()->json([
            'sucesso' => true,
            'dados' => $paginate->items(),
            'pagina' => $paginate->currentPage(),
            'por_pagina' => $paginate->perPage(),
            'total' => $paginate->total()
        ], 200);
    }


    // Função para buscar apenas um doador
    public function buscarDoador($doadorId)
    {
        $doador = Doador::with('doacao')->find($doadorId);

        if (is_null($doador)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Doador não encontrado'
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => $doador
        ]);
    }


    // Função de criar um doador, validando
    public function criarDoador(Request $request, DoadorValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            $dadosValidados['cpf'] = preg_replace('/\D/', '', $dadosValidados['cpf']);
            $dadosValidados['telefone'] = preg_replace('/\D/', '', $dadosValidados['telefone']);

            $dataHoje = Carbon::now();
            $dataNascimento = $request->input('data_de_nascimento');

            $idade = $dataHoje->diffInYears(Carbon::parse($dataNascimento));

            // Validação de idade.
            if ($idade < 16 || $idade > 69) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'A idade mínima para ser um doador é de: 16 e máxima de: 69. Menor de 16 anos precisa de autorização de um responsável.'
                ], 409);
            }
            $dadosValidados['cep'] = preg_replace('/\D/', '', $dadosValidados['cep']);
            $DadosEndereco = null;

            if (!is_null($dadosValidados['cep']) && !empty($dadosValidados['cep'])) {

                $client = new Client([
                    'timeout' => 10
                ]);


                $response = $client->get("https://brasilapi.com.br/api/cep/v2/{$dadosValidados['cep']}");
                $DadosEndereco = json_decode($response->getBody(), true);
                 
                $dadosValidados['endereco_origem'] = 'api';
            }
            $dadosValidados['cidade'] = $dadosValidados['cidade'] ?? $DadosEndereco['city'];
            $dadosValidados['bairro'] = $dadosValidados['bairro'] ?? $DadosEndereco['neighborhood'];
            $dadosValidados['uf'] = $dadosValidados['uf'] ?? $DadosEndereco['state'];
            $dadosValidados['logradouro'] = $dadosValidados['logradouro'] ?? $DadosEndereco['street'];

            if(!is_null($dadosValidados['cep']) && !is_null($dadosValidados['logradouro']) && !is_null($dadosValidados['numero']) && !is_null($dadosValidados['complemento']) && !is_null($dadosValidados['bairro']) 
                && !is_null($dadosValidados['cidade']) && !is_null($dadosValidados['uf'])){
                $dadosValidados['endereco_origem'] = 'manual';
            }


            $doador = Doador::create($dadosValidados);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doador criado com sucesso',
                'dados' => array_merge($doador->toArray(), ['idade' => $idade]),
                'endereco' => $DadosEndereco

            ], 201);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'email_UNIQUE')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Email já em uso',

                ], 409);
            }

            if (str_contains($e->getMessage(), 'cpf_UNIQUE')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'CPF já em uso'
                ], 409);
            }

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao criar doador',
            ], 500);
        }
    }


    // Função para atualizar doador
    public function atualizarDoador(Request $request, DoadorValidator $validador, int $doadorId)
    {
        try {

            $doador = Doador::find($doadorId);

            if (is_null($doador)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doador não encontrado'
                ], 404);
            }

            $dadosValidados = $validador->validate($request);

            $dadosValidados['cpf'] = preg_replace('/\D/', '', $dadosValidados['cpf']);
            $dadosValidados['telefone'] = preg_replace('/\D/', '', $dadosValidados['telefone']);

            $doador->update($dadosValidados);


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doador atualizado com sucesso'

            ], 200);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'email_UNIQUE')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Email já em uso'
                ], 409);
            }

            if (str_contains($e->getMessage(), 'cpf_UNIQUE')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'CPF já em uso'
                ], 409);
            }

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar doador',
            ], 500);
        }
    }

    // Função para deletar doador
    public function deletarDoador($doadorId)
    {
        try {
            $doador = Doador::find($doadorId);

            if (is_null($doador)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doador não encontrado'
                ], 404);
            }


            $doacao = Doacao::where('doador_id', $doador->id);

            // Se o doador tiver uma doação, da erro e atualiza para inativo ao invés de deletar.
            if ($doacao) {
                $doador->update(['status' => 'inativo']);
                return response()->json([
                    'sucesso' => true,
                    'mensagem' => 'Doador não pode ser deletado pois já tem uma doação associada, mas foi inativado.'
                ], 409);
            }

            $doador->delete();


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doador deletado com sucesso'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao deletar doador',
            ], 500);
        }
    }
}
