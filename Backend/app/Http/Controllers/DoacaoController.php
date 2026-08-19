<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoacaoValidator;
use App\Http\Validators\DoacaoValidatorCancela;
use App\Http\Validators\DoacaoValidatorColeta;
use App\Http\Validators\DoacaoValidatorTriagem;
use App\Http\Validators\DoadorValidator;
use App\Models\Bolsa;
use App\Models\Doacao;
use App\Models\Doacao_Historico;
use App\Models\DoacaoCancela;
use App\Models\DoacaoColeta;
use App\Models\DoacaoTriagem;
use App\Models\Doador;
use App\Models\Unidade;
use Carbon\Carbon;
use DateTime;
use Firebase\JWT\JWT;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class DoacaoController extends Controller
{
    // Função de listar doações e filtragem por tipo_sanguineo, status, unidade, data inicio, data fim, ordenacão e etc
    public function listarDoacoes(Request $request)
    {
        $query = Doacao::query()->with('usuario')->with('doacao_historico')->with('unidade')->with('doador')->with('bolsa');

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

        $ordenacaoPermitida = ['id', 'doador_id', 'unidade_id', 'usuario_id', 'status', 'data_e_hora_agendada', 'do_em'];
        if (!in_array($ordenar, $ordenacaoPermitida, true)) {
            $ordenar = 'id';
        }

        if ($busca !== '') {
            $query->whereHas('doador', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        if (!empty($tipoSanguineo)) {
            $query->whereHas('doador', function ($q) use ($tipoSanguineo) {
                $q->where('tipo_sanguineo', $tipoSanguineo);
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($unidadeId)) {
            $query->where('unidade_id', $unidadeId);
        }

        if (!empty($dataInicio) || !empty($dataFim)) {
            $inicio = !empty($dataInicio) ? $dataInicio : '1900-01-01 00:00:00';
            $fim = !empty($dataFim) ? $dataFim : date('Y-m-d H:i:s');
            $query->whereBetween('data_e_hora_agendada', [$inicio, $fim]);
        }

        $paginated = $query->orderBy($ordenar, $direcao === 'asc' ? 'asc' : 'desc')
            ->paginate($porPagina);

        return response()->json([
            'sucesso' => true,
            'dados' => $paginated->items(),
            'pagina' => $paginated->currentPage(),
            'por_pagina' => $paginated->perPage(),
            'total' => $paginated->total()
        ], 200);
    }


    // Função para voltar o histórico, junto com usuário, unidade, bolsa e doador da doação.
    public function historico($doacaoId)
    {
        $doacao = Doacao::with('usuario')->with('doacao_historico')->with('unidade')->with('doador')->with('bolsa')->find($doacaoId);

        if (is_null($doacao)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Doação não encontrada'
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => $doacao
        ]);
    }


    // Busca a doação específica.
    public function buscarDoacao($doacaoId)
    {
        $doacao = Doacao::with('usuario')->with('doacao_historico')->with('unidade')->with('doador')->with('bolsa')->find($doacaoId);

        if (is_null($doacao)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Doação não encontrada'
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => $doacao
        ]);
    }


    // Função para validar o intervalo das doações
    private function validarIntervaloEQuantidadeDoacoes(int $doadorId): ?string
    {
        $doador = Doador::find($doadorId);

        if (is_null($doador)) {
            return 'Doador não encontrado';
        }

        $sexo = strtolower((string) $doador->sexo);
        $intervaloMinimoDias = $sexo === 'feminino' ? 90 : 60;

        $ultimaColeta = Doacao::where('doador_id', $doadorId)
            ->where('status', 'coletada')
            ->whereNotNull('coletado_em')
            ->orderByDesc('coletado_em')
            ->first();

        if (!is_null($ultimaColeta) && !is_null($ultimaColeta->coletado_em)) {
            $diasDesdeUltimaColeta = Carbon::now()->diffInDays(Carbon::parse($ultimaColeta->coletado_em));

            if ($diasDesdeUltimaColeta < $intervaloMinimoDias) {
                return 'Intervalo mínimo entre doações não atendido. Mínimo de ' . $intervaloMinimoDias . ' dias desde a última coleta.';
            }
        }

        $inicioUltimos12Meses = Carbon::now()->subYear();
        $quantidadeUltimos12Meses = Doacao::where('doador_id', $doadorId)
            ->where('status', 'coletada')
            ->whereNotNull('coletado_em')
            ->where('coletado_em', '>=', $inicioUltimos12Meses->toDateTimeString())
            ->count();

        $maximoDoacoes = $sexo === 'feminino' ? 3 : 4;

        if ($quantidadeUltimos12Meses >= $maximoDoacoes) {
            return 'O doador atingiu o limite de ' . $maximoDoacoes . ' doações coletadas nos últimos 12 meses.';
        }

        return null;
    }


    // Função para agendar doação
    public function agendarDoacao(Request $request, DoacaoValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            // Valida intervalo de doação
            $erroRegra = $this->validarIntervaloEQuantidadeDoacoes((int) $dadosValidados['doador_id']);
            if (!is_null($erroRegra)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => $erroRegra
                ], 422);
            }

            $data = date('Y-m-d H:i:s');

            $dadosValidados['data_e_hora_agendada'] = $data;

            // Buscar unidade
            $unidade = Unidade::find($dadosValidados['unidade_id']);

            if (is_null($unidade)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Unidade não encontrada'
                ], 404);
            }

            // Busca quantas doações existem na unidade
            $doacoesNaUnidade = Doacao::where('unidade_id', $dadosValidados['unidade_id'])->Orwhere('status', '=', 'agendada')->Orwhere('status', '=', 'triagem')->Orwhere('status', 'coletada')->count();

            $doador = Doador::find($request->input('doador_id'));

            if (is_null($doador)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doador não encontrado'
                ], 404);
            }

            if ($doador->status === 'inativo') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doador inativo impossibilitado de agendar uma doação.'
                ], 409);
            }

            if ($doacoesNaUnidade >= $unidade->capacidade_diaria) {
                return response()->json([
                    'sucesso' => true,
                    'mensagem' => 'Capacidade máxima diária atingida na unidade'
                ], 409);
            }

            // Insere na tabela de histórico uma nova linha sobre a doação.
            $usuario = $request->auth;
            $doacao = Doacao::create($dadosValidados);
            Doacao_Historico::create([
                'doacao_id' => $doacao->id,
                'status_de_origem' => 'Agendamento de doação',
                'status_de_destino' => 'Triagem de doação',
                'usuario_id' => $usuario['id'],
                'motivo' => 'Agendamento de uma doação de um doador',
                'data_e_hora' => Carbon::now('America/Sao_Paulo')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doação criada com sucesso',
                'dados' => $doacao,

            ], 201);
        } catch (QueryException $e) {




            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao criar doação',
            ], 500);
        }
    }


    // Função chamada para fazer a triagem da doação
    public function triagem(Request $request, DoacaoValidatorTriagem $validador, int $doacaoId)
    {
        try {

            $doacao = DoacaoTriagem::find($doacaoId);

            if (is_null($doacao)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doação não encontrada'
                ], 404);
            }

            $dadosValidados = $validador->validate($request);
            $dadosValidados['status'] = 'triagem';

            // Valida anemia
            if (
                $dadosValidados['peso'] < 50 &&
                (
                    ($dadosValidados['sexo'] === 'feminino' && $dadosValidados['hemoglobina'] < 12.5) ||
                    ($dadosValidados['sexo'] === 'masculino' && $dadosValidados['hemoglobina'] < 13)
                )
            ) {
                $doacao->update([
                    'status' => 'recusada',
                    'motivo_da_recusa' => 'Hemoglobina e/ou peso muito abaixo do esperado'
                ]);
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Triagem não aprovada, devido a: peso abaixo ou hemoglobina muito baixa'
                ]);
            }

            // Se tiver coletada, cancelada ou recusada não pode alterar

            if ($doacao->status === 'coletada' || $doacao->status === 'cancelada' || $doacao->status === 'recusada') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Não é possível alterar uma doação coletada, cancelada ou recusada'
                ], 409);
            }

            $usuario = $request->auth;


            // Insere histórico
            $doacao->update($dadosValidados);
            Doacao_Historico::create([
                'doacao_id' => $doacao->id,
                'status_de_origem' => 'Triagem de doação',
                'status_de_destino' => 'Coleta da doação',
                'usuario_id' => $usuario['id'],
                'motivo' => 'Triagem de uma doação de um doador',
                'data_e_hora' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Triagem da doação realizada com sucesso'

            ], 200);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar triagem da doação',
            ], 500);
        }
    }


    // Função para coleta da doação
    public function coleta(Request $request, DoacaoValidatorColeta $validador, int $doacaoId)
    {
        try {

            $doacao = DoacaoColeta::with('doador')->find($doacaoId);
            if (is_null($doacao)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doação não encontrada'
                ], 404);
            }

            $dadosValidados = $validador->validate($request);


            // Validação de volume, se não estiver entre 400 e 500 da erro
            if ($dadosValidados['volume_coletado'] < 400 || $dadosValidados['volume_coletado'] > 500) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'O volume coletado precisa estar entre 400ml e 500ml'
                ], 409);
            }

            if ($doacao->status === 'agendada') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => "Não é possível fazer a coleta de uma doação ainda agendada"
                ], 409);
            }

            if ($doacao->status === 'cancelada' || $doacao->status === 'recusada') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Não é possível alterar uma doação cancelada ou recusada'
                ], 409);
            }

            $doacao->update($dadosValidados);

            // Formatação de um código unico, com o id da doação.
            $prefixo = 'BS';
            $ano = date('Y');

            $codigo = sprintf('%s-%s-%06d', $prefixo, $ano, $doacao->id);

            $doador = $doacao->doador;

            $coletadoEm = date('Y-m-d H:i:s');
            $venceEm = date('Y-m-d H:i:s', strtotime('+35 days'));

            $usuario = $request->auth;

            $buscarDoacao = Doacao::where('usuario_id', $usuario['id'])->first();

            if (empty($buscarDoacao)) {

                Bolsa::create([
                    'doacao_id' => $doacao->id,
                    'codigo' => $codigo,
                    'tipo_sanguineo' => $doador->tipo_sanguineo ?? 'A+',
                    'coletado_em' => $coletadoEm,
                    'vence_em' => $venceEm,
                    'status' => 'disponivel'
                ]);
            }

            Doacao_Historico::create([
                'doacao_id' => $doacao->id,
                'status_de_origem' => 'Coleta de doação',
                'status_de_destino' => 'Disponibilização para o recebedor',
                'usuario_id' => $usuario['id'],
                'motivo' => 'Coleta de uma doação de um doador',
                'data_e_hora' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Coleta da doação realizada com sucesso'

            ], 200);
        } catch (QueryException $e) {
            
            if(str_contains($e->getMessage(), 'doacao_id_UNIQUE')){
                return response()->json([
                'sucesso' => false,
                'mensagem' => 'Doação já coletada' 
            ], 409);
            }

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar coleta da doação' 
            ], 500);
        }
    }


    // Função para cancelar a doação
    public function cancela(Request $request, DoacaoValidatorCancela $validador, int $doacaoId)
    {
        try {

            $doacao = DoacaoCancela::find($doacaoId);

            if (is_null($doacao)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Doação não encontrada'
                ], 404);
            }





            $doacao->update(['status' => 'cancelada']);
            $usuario = $request->auth;

            // Insere mais uma linha no histórico
            Doacao_Historico::create([
                'doacao_id' => $doacao->id,
                'status_de_origem' => 'Cancelamento de doação',
                'status_de_destino' => 'Histórico no sistema',
                'usuario_id' => $usuario['id'],
                'motivo' => $request->input('motivo_da_recusa') ?? 'Cancelamento sem motivo prévio',
                'data_e_hora' => date('Y-m-d H:i:s')
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Cancelamento da doação realizada com sucesso'

            ], 200);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar cancelamento da doação',
            ], 500);
        }
    }
}
