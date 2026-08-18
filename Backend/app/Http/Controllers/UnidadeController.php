<?php

namespace App\Http\Controllers;

use App\Http\Validators\UnidadeValidator;
use App\Models\Unidade;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;

class UnidadeController extends Controller
{

    public function listarUnidades()
    {
        $unidades = Unidade::with('doacao')->get();


        return response()->json([
            'sucesso' => true,
            'dados' => $unidades
        ], 200);
    }

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

    public function criarUnidade(Request $request, UnidadeValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            $unidade = Unidade::create($dadosValidados);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Unidade criada com sucesso',
                'dados' => $unidade
            ], 201);
        } catch (QueryException $e) {

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao criar unidade',
                'erro' => $e->getMessage()
            ], 500);
        }
    }

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
                'erro' => $e->getMessage()
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
                'erro' => $e->getMessage()
            ], 500);
        }
    }
}
