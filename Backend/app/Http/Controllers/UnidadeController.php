<?php

namespace App\Http\Controllers;

use App\Http\Validators\UnidadeValidator;
use App\Models\Doacao;
use App\Models\Doador;
use App\Models\Endereco;
use App\Models\Unidade;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class UnidadeController extends Controller
{
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

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;

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

            $cep = preg_replace('/\D/', '', $dadosValidados['cep']);
            $dadosEndereco = [];

            if (!empty($cep) && strlen($cep) === 8) {
                $client = new Client([
                    'timeout' => 10,
                    'http_errors' => false,
                ]);

                try {
                    $response = $client->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

                    if ($response->getStatusCode() === 200) {
                        $dadosEndereco = json_decode((string) $response->getBody(), true) ?? [];
                    }
                } catch (\Throwable $e) {
                    return response()->json([
                        'sucesso' => false,
                        'mensagem' => 'Erro ao consultar o CEP'
                    ], 422);
                }
            }


            // Usa array_filter/filled para garantir que strings vazias não sobrescrevam a API
            $logradouro = !empty($dadosValidados['logradouro']) ? $dadosValidados['logradouro'] : ($dadosEndereco['street'] ?? null);
            $bairro     = !empty($dadosValidados['bairro'])     ? $dadosValidados['bairro']     : ($dadosEndereco['neighborhood'] ?? null);
            $cidade     = !empty($dadosValidados['cidade'])     ? $dadosValidados['cidade']     : ($dadosEndereco['city'] ?? null);
            $uf         = !empty($dadosValidados['uf'])         ? $dadosValidados['uf']         : ($dadosEndereco['state'] ?? null);

            $latitude  = $dadosValidados['latitude']  ?? ($dadosEndereco['location']['coordinates']['latitude'] ?? null);
            $longitude = $dadosValidados['longitude'] ?? ($dadosEndereco['location']['coordinates']['longitude'] ?? null);

            $endereco = Endereco::create([
                'cep'         => $cep,
                'logradouro'  => $logradouro,
                'numero'      => !empty($dadosValidados['numero']) ? $dadosValidados['numero'] : 'Sem número',
                'complemento' => !empty($dadosValidados['complemento']) ? $dadosValidados['complemento'] : 'Sem complemento',
                'bairro'      => $bairro,
                'cidade'      => $cidade,
                'uf'          => $uf,
                'latitude'    => $latitude,
                'longitude'   => $longitude,
            ]);

            $unidade = Unidade::create([
                'nome'              => $dadosValidados['nome'],
                'endereco_id'       => $endereco->id,
                'capacidade_diaria' => $dadosValidados['capacidade_diaria'] ?? 0,
                'latitude'          => $latitude,
                'longitude'         => $longitude,
            ]);

            return response()->json([
                'sucesso'  => true,
                'mensagem' => 'Unidade criada com sucesso',
                'dados'    => $unidade->load('endereco'),
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'sucesso'  => false,
                'mensagem' => 'Erro ao criar unidade',
                'erro'     => $e->getMessage(),
            ], 500);
        }
    }

    public function unidadeMaisProxima(Request $request)
    {
        $doadorId = $request->input('doador_id');
        if ($doadorId === null) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Referência do doador é obrigatório.'
            ], 400);
        }

        $doador = Doador::find($doadorId);
        if (empty($doador)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Doador não encontrado.'
            ], 404);
        }

        $endereco = Endereco::find($doador->endereco_id);
        if (!$endereco || !$endereco->latitude || !$endereco->longitude) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Endereço do doador não possui coordenadas válidas.'
            ], 400);
        }

        $latitudeUsuario = (float) $endereco->latitude;
        $longitudeUsuario = (float) $endereco->longitude;

        $unidades = Unidade::with('endereco')->get();
        $unidadesProximas = [];

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

            $unidadesProximas[] = [
                'unidade_id'   => $unidade->id,
                'nome'         => $unidade->nome,
                'latitude'     => $latUnidade,
                'longitude'    => $lonUnidade,
                'distancia_km' => round($distancia, 2),
            ];
        }

        if (empty($unidadesProximas)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Nenhuma unidade com coordenadas válidas foi encontrada.'
            ], 404);
        }

        // Ordena do menor para o maior de acordo com a distância em km
        usort($unidadesProximas, function ($a, $b) {
            return $a['distancia_km'] <=> $b['distancia_km'];
        });

        return response()->json([
            'sucesso' => true,
            'dados'   => $unidadesProximas
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

    public function briefing(Request $request, $id)
    {
        // Pega a data passada via Query String (?data=YYYY-MM-DD)
        $dataInput = $request->query('data');

        if (!$dataInput) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'O parâmetro de data é obrigatório.'
            ], 400);
        }

        $unidade = Unidade::find($id);

        if(empty($unidade)){
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Unidade não encontrada para briefing'
            ], 404);
        }
        $dataPedida = Carbon::parse($dataInput);
        $hoje = Carbon::today();

        // RN26: Validação do limite de 16 dias no futuro
        if ($dataPedida->gt($hoje->copy()->addDays(16))) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'A data informada excede o limite de 16 dias para previsão.'
            ], 422);
        }

        $avisos = [];
        $previsao = null;

        // RN25: Verificação de lotação (> 80% da capacidade)
        $totalAgendamentos = Doacao::where('unidade_id', $id)
            ->whereDate('data_e_hora_agendada', $dataPedida)
            ->count();

        if ($unidade->capacidade_diaria > 0) {
            $percentualOcupacao = ($totalAgendamentos / $unidade->capacidade_diaria) * 100;
            if ($percentualOcupacao > 80) {
                $avisos[] = 'unidade perto da lotação';
            }
        }

        // RN26: Consulta de previsão apenas para datas atuais ou futuras
        if ($dataPedida->gte($hoje)) {
            try {
                $client = new Client([
                    'timeout' => 10,
                    'http_errors' => false,
                ]);

                $dataFormatada = $dataPedida->toDateString();

                $response = $client->get('https://api.open-meteo.com/v1/forecast', [
                    'query' => [
                        'latitude'  => $unidade->latitude,
                        'longitude' => $unidade->longitude,
                        'start_date' => $dataFormatada,
                        'end_date'   => $dataFormatada,
                        'daily'      => ['temperature_2m_max', 'precipitation_probability_max'],
                        'timezone'   => 'auto',
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $dadosClima = json_decode((string) $response->getBody(), true);
                    $previsao = $dadosClima;

                    $tempMaxima  = $dadosClima['daily']['temperature_2m_max'][0] ?? 0;
                    $chanceChuva = $dadosClima['daily']['precipitation_probability_max'][0] ?? 0;

                    if ($chanceChuva >= 70) {
                        $avisos[] = 'risco de falta: chuva provável';
                    }

                    if ($tempMaxima >= 32) {
                        $avisos[] = 'reforçar hidratação e sala de espera';
                    }
                } else {
                    $avisos[] = 'Falha ao obter a previsão do tempo.';
                }
            } catch (\Throwable $e) {
                $avisos[] = 'Falha ao consultar serviço de previsão do tempo.';
            }
        }

        return response()->json([
            'sucesso'  => true,
            'unidade'  => $unidade->nome,
            'data'     => $dataPedida->toDateString(),
            'previsao' => $previsao,
            'avisos'   => $avisos,
        ], 200);
    }
}
