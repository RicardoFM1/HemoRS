<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/unidades'], function () use ($router) {
    $router->get('/', 'UnidadeController@listarUnidades');
    $router->get('/{unidadeId}', 'UnidadeController@buscarUnidade');
    $router->post('/', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'UnidadeController@criarUnidade']);
    $router->patch('/{unidadeId}', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'UnidadeController@atualizarUnidade']);
    $router->delete('/{unidadeId}', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UnidadeController@deletarUnidade']);
});
