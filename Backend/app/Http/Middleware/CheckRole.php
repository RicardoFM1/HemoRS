<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{

    public function handle($request, Closure $next, ...$roles)
    {
        $usuario = $request->auth;

        if (!$usuario || !in_array($usuario['perfil'], $roles)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Usuário sem permissão'
            ], 403);
        }

        return $next($request);
    }
}
