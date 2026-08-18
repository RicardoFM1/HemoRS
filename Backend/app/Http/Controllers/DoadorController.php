<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoadorValidator;
use App\Models\Doador;
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

    public function buscarDoador($doadorId){
        $doador = Doador::with('doacao')->find($doadorId);

        if(is_null($doador)){
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


            $doador = Doador::create($dadosValidados);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doador criado com sucesso',
                'dados' => $doador
            ], 201);

        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'email')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Email já em uso',
                    'erro' => $e->getMessage()
                ], 409);
            }

            if (str_contains($e->getMessage(), 'cpf')) {
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
            if (str_contains($e->getMessage(), 'email')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Email já em uso'
                ], 409);
            }

            if (str_contains($e->getMessage(), 'cpf')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'CPF já em uso'
                ], 409);
            }

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar doador',
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    public function deletarDoador ($doadorId){
        try{
            $doador = Doador::find($doadorId);

            if(is_null($doador)){
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
        }catch(QueryException $e){
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao deletar doador',
                'erro' => $e->getMessage()
            ], 500);
        }
    }
}
