<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// Criar um cliente 


$router->get('/Clima', function () {
    
    
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://archive-api.open-meteo.com/v1/archive?latitude=52.52&longitude=13.41&start_date=2000-01-01&end_date=2009-12-31&hourly=temperature_2m&timezone=America%2FSao_Paulo',
        'timeout' => 2
    ]);



    $response = $client->request('GET', '/');

    $body = $response->getBody();

    return response()->json([
        'sucesso' => true,
        'dados' => $body
    ]);
});
