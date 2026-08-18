<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doacao extends Model
{
    protected $table = 'doacao';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'doador_id',
        'unidade_id',
        'data_e_hora_agendada',
        'status',
        'peso',
        'hemoglobina',
        'motivo_da_rescusa',
        'volume_coletado',
        'coletado_em',
        'usuario_id'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    public function doacao_historico()
    {
        return $this->hasMany(Doacao_historico::class, 'doacao_id', 'id');
    }

    public function doador() {
        return $this->belongsTo(Doador::class, 'doador_id', 'id');
    }

    public function unidade()
    {
        return $this->belongsTo(Unidade::class, 'unidade_id', 'id');
    }
}
