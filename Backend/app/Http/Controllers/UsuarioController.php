<?php

namespace App\Http\Controllers;

use App\Http\Validators\UsuarioValidator;
use App\Models\Usuario;
use Firebase\JWT\JWT;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller;

class UsuarioController extends Controller
{

    public function listarUsuarios()
    {
        $usuarios = Usuario::all();

        $usuarios->makeHidden(['senha']);

        return response()->json([
            'sucesso' => true,
            'dados' => $usuarios
        ], 200);
    }

    public function criarUsuario(Request $request, UsuarioValidator $validador)
    {
        try {

            $dadosValidados = $validador->validate($request);

            $dadosValidados['senha'] = Hash::make($dadosValidados['senha']);

            $usuario = Usuario::create($dadosValidados);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário criado com sucesso',
                'dados' => $usuario
            ], 201);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'email')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Email já em uso'
                ], 409);

                }
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao criar usuario',
                'erro' => $e->getMessage()
                ], 500);
        }
    }


    public function fazerLogin(Request $request)
    {

        $usuario = Usuario::where('email', $request->only('email'))->first();

        if (is_null($usuario)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Credenciais inválidas'
            ], 401);
        }

        $senhaCorreta = Hash::check($request->input('senha'), $usuario->senha);

        if (!$senhaCorreta) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Credenciais inválidas'
            ], 401);
        }

        $payload = [
            'exp' => time() + 3600,
            'dados' => [
                'id' => $usuario->id,
                'perfil' => $usuario->perfil
            ]
        ];

        $jwt = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Usuário logado com sucesso',
            'token' => $jwt
        ], 200);
    }
}
