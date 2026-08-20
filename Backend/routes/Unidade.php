<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/unidades'], function () use ($router) {
    $router->get('/', ['middleware' => 'auth', 'uses' => 'UnidadeController@listarUnidades']);
    $router->get('/proximas', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'UnidadeController@unidadeMaisProxima']);
    $router->get('/{unidadeId}', ['middleware' => 'auth', 'uses' => 'UnidadeController@buscarUnidade']);
    $router->post('/', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'UnidadeController@criarUnidade']);
    $router->patch('/{unidadeId}', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'UnidadeController@atualizarUnidade']);
    $router->delete('/{unidadeId}', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UnidadeController@deletarUnidade']);
    $router->get('/{id}/briefing', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'UnidadeController@briefing']);
});
