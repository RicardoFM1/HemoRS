<?php

namespace App\Http\Controllers;

use App\Http\Validators\UnidadeValidator;
use App\Models\Endereco;
use App\Models\Unidade;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Lumen\Routing\Controller;

class UnidadeController extends Controller
{
    private function buscarEnderecoPorCep(string $cep): ?array
    {
        $cepLimpo = preg_replace('/\D/', '', $cep) ?? '';

        if ($cepLimpo === '') {
            return null;
        }

        $cacheKey = 'cep_brasilapi_' . $cepLimpo;

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($cepLimpo) {
            $client = new Client([
                'timeout' => 10,
                'http_errors' => false,
            ]);

            try {
                $response = $client->get("https://brasilapi.com.br/api/cep/v2/{$cepLimpo}");

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $dados = json_decode((string) $response->getBody(), true);

                return is_array($dados) && !empty($dados) ? $dados : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    // Função de listagem de unidades junto com a doação
    public function listarUnidades()
    {
        $unidades = Unidade::with('doacao')->get();


        return response()->json([
            'sucesso' => true,
            'dados' => $unidades
        ], 200);
    }


    // Função para buscar a unidade específica
    public function buscarUnidade($unidadeId)
    {
        $unidade = Unidade::with('doacao')->find($unidadeId);

        if (is_null($unidade)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Unidade não encontrada'
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => $unidade
        ]);
    }


    // Criar uma unidade, sendo validada
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371; // raio da Terra em km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }

    public function criarUnidade(Request $request, UnidadeValidator $validador)
    {
        try {
            $dadosValidados = $validador->validate($request);

            $cep = preg_replace('/\D/', '', $dadosValidados['cep'] ?? '');

            $dadosEndereco = null;

            if (!empty($cep)) {
                $dadosEndereco = $this->buscarEnderecoPorCep($cep);
            }

            $logradouro = $dadosValidados['logradouro'] ?? ($dadosEndereco['street'] ?? null);
            $bairro = $dadosValidados['bairro'] ?? ($dadosEndereco['neighborhood'] ?? null);
            $cidade = $dadosValidados['cidade'] ?? ($dadosEndereco['city'] ?? null);
            $uf = $dadosValidados['uf'] ?? ($dadosEndereco['state'] ?? null);

            $latitude = $dadosValidados['latitude'] ?? ($dadosEndereco['location']['coordinates']['latitude'] ?? null);
            $longitude = $dadosValidados['longitude'] ?? ($dadosEndereco['location']['coordinates']['longitude'] ?? null);

            $endereco = Endereco::create([
                'cep' => $cep,
                'logradouro' => $logradouro,
                'numero' => $dadosValidados['numero'] ?? 'Sem número',
                'complemento' => $dadosValidados['complemento'] ?? 'Sem complemento',
                'bairro' => $bairro,
                'cidade' => $cidade,
                'uf' => $uf,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            $unidade = Unidade::create([
                'nome' => $dadosValidados['nome'],
                'endereco_id' => $endereco->id,
                'capacidade_diaria' => $dadosValidados['capacidade_diaria'] ?? 0,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Unidade criada com sucesso',
                'dados' => $unidade->load('endereco'),
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao criar unidade',
                'erro' => $e->getMessage(),
            ], 500);
        }
    }

    public function unidadeMaisProxima(Request $request)
    {
        $latitudeUsuario = (float) $request->input('latitude');
        $longitudeUsuario = (float) $request->input('longitude');

        $unidades = Unidade::with('endereco')->get();

        $maisProxima = null;
        $menorDistancia = INF;

        foreach ($unidades as $unidade) {
            $latUnidade = (float) ($unidade->latitude ?? $unidade->endereco->latitude ?? 0);
            $lonUnidade = (float) ($unidade->longitude ?? $unidade->endereco->longitude ?? 0);

            if ($latUnidade == 0 || $lonUnidade == 0) {
                continue;
            }

            $distancia = $this->haversine(
                $latitudeUsuario,
                $longitudeUsuario,
                $latUnidade,
                $lonUnidade
            );

            if ($distancia < $menorDistancia) {
                $menorDistancia = $distancia;
                $maisProxima = [
                    'unidade_id' => $unidade->id,
                    'nome' => $unidade->nome,
                    'latitude' => $latUnidade,
                    'longitude' => $lonUnidade,
                    'distancia_km' => round($distancia, 2),
                ];
            }
        }

        return response()->json([
            'sucesso' => true,
            'dados' => $maisProxima
        ], 200);
    }


    // Atualizar uma unidade com o id dela e validando
    public function atualizarUnidade(Request $request, UnidadeValidator $validador, int $unidadeId)
    {
        try {

            $unidade = Unidade::find($unidadeId);

            if (is_null($unidade)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Unidade não encontrada'
                ], 404);
            }

            $dadosValidados = $validador->validate($request);



            $unidade->update($dadosValidados);


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Unidade atualizada com sucesso'
            ], 200);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar unidade',
            ], 500);
        }
    }

    // Função para deletar unidade pelo id
    public function deletarUnidade($unidadeId)
    {
        try {
            $unidade = Unidade::find($unidadeId);

            if (is_null($unidade)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Unidade não encontrada'
                ], 404);
            }

            $unidade->delete();


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Unidade deletada com sucesso'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao deletar unidade',
            ], 500);
        }
    }

    public function briefing (Request $request) {
        $data = $request->query('data', '');


    }

}
