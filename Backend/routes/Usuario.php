<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/usuarios'], function () use ($router) {

    $router->get('/', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@listarUsuarios']);
    $router->get('/{usuarioId}', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@buscarUsuario']);
    $router->post('/', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@criarUsuario']);
    $router->patch('/{usuarioId}', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@atualizarUsuario']);
    $router->delete('/{usuarioId}', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@deletarUsuario']);
});

$router->post('/auth/login', 'UsuarioController@fazerLogin');
