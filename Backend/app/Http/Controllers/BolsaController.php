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
use Carbon\Carbon;
use DateTime;
use Firebase\JWT\JWT;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class BolsaController extends Controller
{


    // Listagem de bolsas
    public function listarBolsas()
    {
        $bolsas = Bolsa::with('doacao')->get();


        return response()->json([
            'sucesso' => true,
            'dados' => $bolsas
        ], 200);
    }






    // Função de reservar uma bolsa
    public function reservar(int $bolsaId)
    {
        try {

            $bolsa = Bolsa::find($bolsaId);

            if (is_null($bolsa)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Bolsa não encontrada'
                ], 404);
            }

            if ($bolsa->status !== 'disponivel') {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Não é possível realizar a reserva de uma bolsa que não está disponível'
                ]);
            }

            $dataAtual = Carbon::now();
            $dataLimite = Carbon::parse($bolsa->vence_em);

            $diferencaDias = $dataAtual->diffInDays($dataLimite);

            if ($diferencaDias <= 0) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Não é possível reservar uma bolsa vencida'
                ]);
            }

            $bolsa->update(['status' => 'reservada']);


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Bolsa reservada com sucesso'

            ], 200);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar reserva da bolsa',
            ], 500);
        }
    }


    // Função de expurgar bolsas, valida as que estão vencidas e expurga elas (status = descartada)
    public function expurgar()
    {
        try {

            $dataAtual = date('Y-m-d H:i:s');

            Bolsa::where('vence_em', '<=', $dataAtual)
                ->update(['status' => 'descartada']);



            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Bolsas vencidas expurgadas com sucesso'
            ]);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao realizar expurgo da bolsa',
            ], 500);
        }
    }
}
