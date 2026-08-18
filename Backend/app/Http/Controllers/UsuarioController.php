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
        $usuarios = Usuario::with('doacao')->with('doacao_historico')->all();

        $usuarios->makeHidden(['senha']);

        return response()->json([
            'sucesso' => true,
            'dados' => $usuarios
        ], 200);
    }

    public function buscarUsuario($usuarioId)
    {
        $usuario = Usuario::with('doacao')->with('doacao_historico')->find($usuarioId);

        $usuario->makeHidden(['senha']);

        return response()->json([
            'sucesso' => true,
            'dados' => $usuario
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
            if (str_contains($e->getMessage(), 'email_UNIQUE')) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Email já em uso'
                ], 409);

                }
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao criar usuario',
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

    public function atualizarUsuario(Request $request, UsuarioValidator $validador, int $usuarioId)
    {
        try {

            $usuario = Usuario::find($usuarioId);

            if (is_null($usuario)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Usuário não encontrado'
                ], 404);
            }

            $dadosValidados = $validador->validate($request);

            $dadosValidados['senha'] = Hash::make($dadosValidados['senha']);

            $usuario->update($dadosValidados);


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário atualizado com sucesso'
            ], 200);
        } catch (QueryException $e) {


            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar usuário',
            ], 500);
        }
    }

    public function deletarUsuario($usuarioId)
    {
        try {
            $usuario = Usuario::find($usuarioId);

            if (is_null($usuario)) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Usuário não encontrado'
                ], 404);
            }

            $usuario->delete();


            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Usuário deletado com sucesso'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao deletar usuário',
            ], 500);
        }
    }
}
