<?php


namespace App\Http\Controllers;

use App\Models\Bolsa;
use Carbon\Carbon;
use Laravel\Lumen\Routing\Controller;

class RelatorioController extends Controller{
    
    public function listarRelatorio () {
        $bolsasDisponiveisDentroDaValidade = Bolsa::where('status', 'disponivel')->where('vence_em', '<=', date('Y-m-d H:i:s'))->selectRaw('tipo_sanguineo, COUNT(*) as total' )->groupBy('tipo_sanguineo')->get();


        $hoje = Carbon::now();
        $daquiA7Dias = Carbon::now()->addDays(7);

        $bolsasQueVencemEm7Dias = Bolsa::where('status', 'disponivel')
            ->whereBetween('vence_em', [$hoje, $daquiA7Dias])
            ->select('id', 'tipo_sanguineo', 'vence_em')
            ->selectRaw('DATEDIFF(vence_em, NOW()) as dias_restantes')
            ->orderBy('tipo_sanguineo', 'asc') // 1º Ordena alfabeticamente por tipo sanguíneo (ex: A+, A-, B+...)
            ->orderBy('vence_em', 'asc')       // 2º Ordena as que vencem primeiro dentro do mesmo tipo
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