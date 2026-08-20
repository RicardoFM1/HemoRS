<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoadorValidator;
use App\Models\Doacao;
use App\Models\Doador;
use App\Models\Endereco;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class DoadorController extends Controller
{
    private function buscarEnderecoPorCep(string $cep): ?array
    {
        $cepLimpo = preg_replace('/\D/', '', $cep) ?? '';

        if ($cepLimpo === '') {
            return null;
        }

        $cacheKey = 'cep_brasilapi_' . $cepLimpo;

        if (Cache::has($cacheKey)) {
            $dados = Cache::get($cacheKey);

            if (is_array($dados) && !empty($dados)) {
                return [
                    'dados' => $dados,
                    'origem' => 'cache',
                ];
            }
        }

        $client = new Client([
            'base_uri' => 'httpbin/delay',
            'timeout' => 10,
            'http_errors' => false,
        ]);

        try {
            $response = $client->get("https://brasilapi.com.br/api/cep/v2/{$cepLimpo}");

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $dados = json_decode((string) $response->getBody(), true);

            if (is_array($dados) && !empty($dados)) {
                Cache::put($cacheKey, $dados, 60 * 60 * 24);

                return [
                    'dados' => $dados,
                    'origem' => 'api',
                ];
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // Função para listar os doadores com filtros
    public function listarDoadores(Request $request)
    {
        $query = Doador::query()->with('doacao')->with('endereco');

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

            $dadosValidados['cpf'] = preg_replace('/\D/', '', $dadosValidados['cpf'] ?? '');
            $dadosValidados['telefone'] = preg_replace('/\D/', '', $dadosValidados['telefone'] ?? '');

            $dataHoje = Carbon::now();
            $dataNascimento = $request->input('data_de_nascimento');

            $idade = $dataHoje->diffInYears(Carbon::parse($dataNascimento));

            if ($idade < 16 || $idade > 69) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'A idade mínima para ser um doador é de: 16 e máxima de: 69. Menor de 16 anos precisa de autorização de um responsável.'
                ], 409);
            }

            $dadosValidados['cep'] = preg_replace('/\D/', '', $dadosValidados['cep'] ?? '');
            $DadosEndereco = null;
            $origemEndereco = 'manual';

            if (!empty($dadosValidados['cep'])) {
                $resultadoCep = $this->buscarEnderecoPorCep($dadosValidados['cep']);

                if (is_array($resultadoCep) && !empty($resultadoCep['dados'])) {
                    $DadosEndereco = $resultadoCep['dados'];
                    $origemEndereco = $resultadoCep['origem'] ?? 'api';
                    $dadosValidados['endereco_origem'] = $origemEndereco;
                }
            }

            $dadosValidados['cidade'] = $dadosValidados['cidade'] ?? ($DadosEndereco['city'] ?? null);
            $dadosValidados['bairro'] = $dadosValidados['bairro'] ?? ($DadosEndereco['neighborhood'] ?? null);
            $dadosValidados['uf'] = $dadosValidados['uf'] ?? ($DadosEndereco['state'] ?? null);
            $dadosValidados['logradouro'] = $dadosValidados['logradouro'] ?? ($DadosEndereco['street'] ?? null);

            $dadosValidados['numero'] = $dadosValidados['numero'] ?? 'Sem número';
            $dadosValidados['complemento'] = $dadosValidados['complemento'] ?? 'Sem complemento';

            if (empty($DadosEndereco) || !is_array($DadosEndereco)) {
                $dadosValidados['endereco_origem'] = 'manual';
            }
            if (
                empty($dadosValidados['logradouro']) ||
                empty($dadosValidados['bairro'])     ||
                empty($dadosValidados['cidade'])     ||
                empty($dadosValidados['uf'])
            ) {
                return response()->json([
                    'sucesso'  => false,
                    'mensagem' => 'Consulta do CEP falhou ou retornou incompleta. Insira os campos: logradouro, bairro, cidade e uf.'
                ], 422);
            }
            // Se a API falhou, continuar normalmente
            $latitude = $DadosEndereco['location']['coordinates']['latitude'] ?? null;
            $longitude = $DadosEndereco['location']['coordinates']['longitude'] ?? null;

            $endereco = Endereco::create([
                'cep' => $dadosValidados['cep'],
                'logradouro' => $dadosValidados['logradouro'],
                'numero' => $dadosValidados['numero'],
                'complemento' => $dadosValidados['complemento'],
                'bairro' => $dadosValidados['bairro'],
                'cidade' => $dadosValidados['cidade'],
                'uf' => $dadosValidados['uf'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'endereco_origem' => $dadosValidados['endereco_origem'] ?? 'manual',
            ]);

            $doador = Doador::create([
                'nome' => $dadosValidados['nome'],
                'cpf' => $dadosValidados['cpf'],
                'data_de_nascimento' => $dadosValidados['data_de_nascimento'],
                'sexo' => $dadosValidados['sexo'],
                'tipo_sanguineo' => $dadosValidados['tipo_sanguineo'],
                'telefone' => $dadosValidados['telefone'],
                'email' => $dadosValidados['email'],
                'status' => $dadosValidados['status'] ?? 'ativo',
                'endereco_id' => $endereco->id
            ]);

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
                'erro' => $e->getMessage()
            ], 500);
        }
    }



    // Função para atualizar doador
    public function atualizarDoador(Request $request, DoadorValidator $validador, int $doadorId)
    {
        try {
            $doador = Doador::with('endereco')->find($doadorId);

            if (is_null($doador)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doador não encontrado'
                ], 404);
            }

            $dadosValidados = $validador->validate($request);

            $dadosValidados['cpf'] = preg_replace('/\D/', '', $dadosValidados['cpf'] ?? '');
            $dadosValidados['telefone'] = preg_replace('/\D/', '', $dadosValidados['telefone'] ?? '');
            $dadosValidados['cep'] = preg_replace('/\D/', '', $dadosValidados['cep'] ?? '');

            $DadosEndereco = null;
            $endereco = $doador->endereco;
            $origemEndereco = 'manual';

            if (!empty($dadosValidados['cep'])) {
                $resultadoCep = $this->buscarEnderecoPorCep($dadosValidados['cep']);

                if (is_array($resultadoCep) && !empty($resultadoCep['dados'])) {
                    $DadosEndereco = $resultadoCep['dados'];
                    $origemEndereco = $resultadoCep['origem'] ?? 'api';
                    $dadosValidados['endereco_origem'] = $origemEndereco;
                }
            }

            $dadosValidados['cidade'] = $dadosValidados['cidade'] ?? ($DadosEndereco['city'] ?? ($endereco->cidade ?? null));
            $dadosValidados['bairro'] = $dadosValidados['bairro'] ?? ($DadosEndereco['neighborhood'] ?? ($endereco->bairro ?? null));
            $dadosValidados['uf'] = $dadosValidados['uf'] ?? ($DadosEndereco['state'] ?? ($endereco->uf ?? null));
            $dadosValidados['logradouro'] = $dadosValidados['logradouro'] ?? ($DadosEndereco['street'] ?? ($endereco->logradouro ?? null));
            $dadosValidados['numero'] = $dadosValidados['numero'] ?? ($endereco->numero ?? 'Sem número');
            $dadosValidados['complemento'] = $dadosValidados['complemento'] ?? ($endereco->complemento ?? 'Sem complemento');

            if (empty($DadosEndereco) || !is_array($DadosEndereco)) {
                $dadosValidados['endereco_origem'] = 'manual';
            }

            $latitude = $DadosEndereco['location']['coordinates']['latitude'] ?? ($endereco->latitude ?? null);
            $longitude = $DadosEndereco['location']['coordinates']['longitude'] ?? ($endereco->longitude ?? null);

            $dadosEnderecoParaSalvar = [
                'cep' => $dadosValidados['cep'],
                'logradouro' => $dadosValidados['logradouro'],
                'numero' => $dadosValidados['numero'],
                'complemento' => $dadosValidados['complemento'],
                'bairro' => $dadosValidados['bairro'],
                'cidade' => $dadosValidados['cidade'],
                'uf' => $dadosValidados['uf'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'endereco_origem' => $dadosValidados['endereco_origem'] ?? 'manual',
            ];

            if ($endereco) {
                $endereco->update($dadosEnderecoParaSalvar);
                $dadosValidados['endereco_id'] = $endereco->id;
            } else {
                $enderecoNovo = Endereco::create($dadosEnderecoParaSalvar);
                $dadosValidados['endereco_id'] = $enderecoNovo->id;
            }

            if (!empty($dadosValidados['cep']) && empty($DadosEndereco)) {
                $camposEnderecoObrigatorios = ['logradouro', 'bairro', 'uf', 'cidade', 'complemento'];
                $faltouEnderecoManual = true;

                foreach ($camposEnderecoObrigatorios as $campo) {
                    if (!empty(trim((string) ($dadosValidados[$campo] ?? '')))) {
                        $faltouEnderecoManual = false;
                        break;
                    }
                }

                if ($faltouEnderecoManual) {
                    return response()->json([
                        'sucesso' => false,
                        'mensagem' => 'Consulta do CEP deu erro, insira os campos: logradouro, complemento, bairro, cidade, uf'
                    ], 422);
                }
            }

            if (isset($dadosValidados['endereco_origem'])) {
                if ($endereco) {
                    $endereco->update(['endereco_origem' => $dadosValidados['endereco_origem']]);
                } elseif (isset($enderecoNovo)) {
                    $enderecoNovo->update(['endereco_origem' => $dadosValidados['endereco_origem']]);
                }
            }

            $doador->update([
                'nome' => $dadosValidados['nome'],
                'cpf' => $dadosValidados['cpf'],
                'data_de_nascimento' => $dadosValidados['data_de_nascimento'],
                'sexo' => $dadosValidados['sexo'],
                'tipo_sanguineo' => $dadosValidados['tipo_sanguineo'],
                'telefone' => $dadosValidados['telefone'],
                'email' => $dadosValidados['email'],
                'status' => $dadosValidados['status'] ?? $doador->status,
                'endereco_id' => $dadosValidados['endereco_id'],
            ]);

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
