<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/doacoes'], function () use ($router) {
    $router->get('/', ['middleware' => 'auth', 'uses' => 'DoacaoController@listarDoacoes'] );
    $router->get('/{doacaoId}', ['middleware' => 'auth', 'uses' => 'DoacaoController@buscarDoacao']);
    $router->post('/', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'DoacaoController@agendarDoacao']);
    $router->patch('/{doacaoId}/triagem', ['middleware' => ['auth', 'role:enfermagem,gestor'], 'uses' => 'DoacaoController@triagem']);
    $router->patch('/{doacaoId}/coleta', ['middleware' => ['auth', 'role:enfermagem,gestor'], 'uses' => 'DoacaoController@coleta']);
    $router->patch('/{doacaoId}/cancelamento', ['middleware' => ['auth', 'role:recepcao,gestor'], 'uses' => 'DoacaoController@cancelar']);
    $router->get('/{doacaoId}/historico', ['middleware' => 'auth', 'uses' => 'DoacaoController@historico']);
});
