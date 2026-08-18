<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doacao_Historico extends Model
{
    protected $table = 'doacao_historico';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'doacao_id',
        'status_de_origem',
        'status_de_destino',
        'usuario_id',
        'motivo',
        'data_e_hora'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    public function doacao_historico()
    {
        return $this->belongsTo(Doacao::class, 'doacao_id', 'id');
    }
}
