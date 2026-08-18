<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/bolsas'], function () use ($router) {
    $router->get('/', ['middleware' => 'auth', 'uses' => 'BolsaController@listarBolsas']);
    $router->patch('/{bolsaId}/reservar', ['middleware' => ['auth', 'role:enfermagem,gestor'], 'uses' => 'BolsaController@reservar']);
    $router->post('/expurgo', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'BolsaController@expurgar']);
});
