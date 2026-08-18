<?php


namespace App\Http\Controllers;

use App\Models\Bolsa;
use Carbon\Carbon;
use Laravel\Lumen\Routing\Controller;

class RelatorioController extends Controller{

    public function listarRelatorio()
    {
        $hoje = Carbon::now();
        $daquiA7Dias = Carbon::now()->addDays(7);

        $bolsasDisponiveisDentroDaValidade = Bolsa::where('status', 'disponivel')
            ->where('vence_em', '>=', $hoje->toDateTimeString())
            ->selectRaw('tipo_sanguineo, COUNT(*) as total')
            ->groupBy('tipo_sanguineo')
            ->get();

        $bolsasQueVencemEm7Dias = Bolsa::where('status', 'disponivel')
            ->where('vence_em', '>=', $hoje->toDateTimeString())
            ->where('vence_em', '<=', $daquiA7Dias->toDateTimeString())
            ->select('id', 'tipo_sanguineo', 'vence_em')
            ->selectRaw('DATEDIFF(vence_em, NOW()) as dias_restantes')
            ->orderBy('tipo_sanguineo', 'asc')
            ->orderBy('vence_em', 'asc')
            ->get();

        return response()->json([
            'sucesso' => true,
            'dados' => [
                'bolsas_disponiveis_dentro_da_validade' => $bolsasDisponiveisDentroDaValidade,
                'bolsas_vencendo_em_7_dias' => $bolsasQueVencemEm7Dias
            ]
        ], 200);
    }
}