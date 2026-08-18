<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/doadores'], function () use ($router) {
    $router->get('/', ['middleware' => 'auth', 'uses' => 'DoadorController@listarDoadores']);
    $router->get('/{doadorId}', ['middleware' => 'auth', 'uses' => 'DoadorController@buscarDoador']);
    $router->post('/', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'DoadorController@criarDoador']);
    $router->patch('/{doadorId}', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'DoadorController@atualizarDoador']);
    $router->delete('/{doadorId}', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'DoadorController@deletarDoador']);
});
