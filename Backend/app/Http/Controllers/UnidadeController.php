<?php

namespace App\Http\Controllers;

use App\Http\Validators\UnidadeValidator;
use App\Models\Unidade;
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


    // Criar uma unidade, sendo validada
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
            ], 500);
        }
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
}
