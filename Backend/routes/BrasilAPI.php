<?php

use GuzzleHttp\Client;

/** @var Laravel/Lumen/Routing/Router $router */





$router->get('/cnpj/{cnpj}', function ($cnpj) {
    $client = new Client([
        'timeout' => 2
    ]);

    $response = $client->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
    $dados = json_decode($response->getBody(), true);
    return response()->json([
        'sucesso' => true,
        'dados' => [
            'intruso' => [
                'uf' => $dados['uf']
            ]
        ]
    ]);
});
