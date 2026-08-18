<?php

namespace App\Http\Controllers;

use App\Http\Validators\DoadorValidator;
use App\Http\Validators\UsuarioValidator;
use App\Models\Doador;
use App\Models\Usuario;
use Firebase\JWT\JWT;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class DoadorController extends Controller
{

    public function listarDoadores()
    {
        $doadores = Doador::all();


        return response()->json([
            'sucesso' => true,
            'dados' => $doadores
        ], 200);
    }

    public function criarDoador(Request $request, DoadorValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            $dadosValidados['cpf'] = preg_replace('/\D/', '', $dadosValidados['cpf']);
            $dadosValidados['telefone'] = preg_replace('/\D/', '', $dadosValidados['telefone']);


            $doador = Usuario::create($dadosValidados);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Doador criado com sucesso',
                'dados' => $doador
            ], 201);
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
        }
    }


    
}
