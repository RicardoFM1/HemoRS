<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
    public function handle($request, Closure $next){
    try{
        $token = $request->header('Authorization');

        if(!$token){
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Usuário não autenticado'
            ], 401);
        }

        $tokenPartes = explode(' ', $token);

        if(count($tokenPartes) !== 2){
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token inválido'
            ], 401);
        }

        $jwt = JWT::decode($tokenPartes[1], new Key(env('JWT_SECRET'), 'HS256'));


        $usuario = [
            'id' => $jwt->dados->id,
            'perfil' => $jwt->dados->perfil
        ];

        $request->auth = $usuario;



        return $next($request);
        }catch(ExpiredException $e){
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token expirado'
            ], 401);
        }
    }
}
