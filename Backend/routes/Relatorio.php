<?php

/** @var Laravel/Lumen/Routing/Router $router */


$router->group(['prefix' => '/relatorios'], function () use ($router) {
    $router->get('/', ['middleware' => ['auth', 'role:gestor'], 'uses' => 'RelatorioController@listarRelatorio']);
});
