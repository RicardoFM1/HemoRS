<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoacaoValidator;
use App\Http\Validators\DoacaoValidatorCancela;
use App\Http\Validators\DoacaoValidatorColeta;
use App\Http\Validators\DoacaoValidatorTriagem;
use App\Http\Validators\DoadorValidator;
use App\Models\Bolsa;
use App\Models\Doacao;
use App\Models\DoacaoCancela;
use App\Models\DoacaoColeta;
use App\Models\DoacaoTriagem;
use App\Models\Doador;
use App\Models\Unidade;
use DateTime;
use Firebase\JWT\JWT;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class DoacaoController extends Controller
{

    public function listarDoacoes()
    {
        $doacoes = Doacao::with('usuario')->with('doacao_historico')->with('unidade')->with('doador')->with('bolsa')->get();


        return response()->json([
            'sucesso' => true,
            'dados' => $doacoes
        ], 200);
    }

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

    public function agendarDoacao(Request $request, DoacaoValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            $data = date('Y-m-d H:i:s');

            $dadosValidados['data_e_hora_agendada'] = $data;

            $unidade = Unidade::find($dadosValidados['unidade_id']);

            $doacoesNaUnidade = Doacao::where('unidade_id', $dadosValidados['unidade_id'])->Orwhere('status', '=', 'agendada')->Orwhere('status', '=', 'triagem')->Orwhere('status','coletada')->count();

         
            if($doacoesNaUnidade >= $unidade->capacidade_diaria){
                return response()->json([
                    'sucesso' => true,
                    'mensagem' => 'Capacidade máxima diária atingida na unidade'
                ]);
            }

            $doacao = Doacao::create($dadosValidados);

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


            if ($doacao->status === 'coletada' || $doacao->status === 'cancelada' || $doacao->status === 'recusada') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Não é possível alterar uma doação coleta, cancelada ou recusada'
                ], 409);
            }


            $doacao->update($dadosValidados);


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



            $doacao->update($dadosValidados);

            // Formatação de um código unico, com o id da doação.
            $prefixo = 'BS';
            $ano = date('Y');

            $codigo = sprintf('%s-%s-%06d', $prefixo, $ano, $doacao->id);

            $doador = $doacao->doador;

            $coletadoEm = date('Y-m-d H:i:s');
            $venceEm = date('Y-m-d H:i:s', strtotime('+35 days'));

            Bolsa::create([
                'doacao_id' => $doacao->id,
                'codigo' => $codigo,
                'tipo_sanguineo' => $doador->tipo_sanguineo ?? 'A+',
                'coletado_em' => $coletadoEm,
                'vence_em' => $venceEm,
                'status' => 'disponivel'
            ]);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Coleta da doação realizada com sucesso'

            ], 200);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar coleta da doação',
            ], 500);
        }
    }



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
