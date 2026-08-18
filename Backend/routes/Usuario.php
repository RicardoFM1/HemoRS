<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/usuarios'], function () use ($router) {

    $router->get('/', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@listarUsuarios']);
    $router->post('/', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UsuarioController@criarUsuario']);
});

$router->post('/auth/login', 'UsuarioController@fazerLogin');
