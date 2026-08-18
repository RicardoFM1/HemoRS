<?php

namespace App\Http\Middleware;

use Closure;

class ApenasGestor
{
    
    public function handle($request, Closure $next)
    {
        $usuario = $request->auth;

        if($usuario['perfil'] !== 'gestor'){
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Usuário sem permissão'
            ], 403);
        }

        return $next($request);
    }
}
