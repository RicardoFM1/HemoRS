<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/usuarios'], function () use ($router) {

    $router->get('/', ['middleware' => ['auth', 'apenasGestor'], 'uses' => 'UsuarioController@listarUsuarios']);
    $router->post('/', ['middleware' => ['auth', 'apenasGestor'], 'uses' => 'UsuarioController@criarUsuario']);
});

$router->post('/auth/login', 'UsuarioController@fazerLogin');
