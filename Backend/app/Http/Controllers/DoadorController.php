<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoadorValidator;
use App\Models\Doador;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class DoadorController extends Controller
{

    public function listarDoadores()
    {
        $doadores = Doador::with('doacao')->get();

       
        return response()->json([
            'sucesso' => true,
            'dados' => $doadores
        ], 200);
    }

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

    public function criarDoador(Request $request, DoadorValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            $dadosValidados['cpf'] = preg_replace('/\D/', '', $dadosValidados['cpf']);
            $dadosValidados['telefone'] = preg_replace('/\D/', '', $dadosValidados['telefone']);

            $dataHoje = Carbon::now();
            $dataNascimento = $request->input('data_de_nascimento');

            $idade = $dataHoje->diffInYears(Carbon::parse($dataNascimento));


            if($idade < 16 || $idade > 69){
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'A idade mínima para ser um doador é de: 16 e máxima de: 69. Menor de 16 anos precisa de autorização de um responsável.'
                ], 409);
            }



            $doador = Doador::create($dadosValidados);
            
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doador criado com sucesso',
                'dados' => array_merge($doador->toArray(), ['idade' => $idade])
                    
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
