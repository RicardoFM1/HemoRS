<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoacaoCancela extends Model
{
    protected $table = 'doacao';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'status',
        'motivo_da_recusa'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    public function doacao_historico()
    {
        return $this->hasMany(Doacao_historico::class, 'doacao_id', 'id');
    }

    public function doador()
    {
        return $this->belongsTo(Doador::class, 'doador_id', 'id');
    }

    public function unidade()
    {
        return $this->belongsTo(Unidade::class, 'unidade_id', 'id');
    }
    public function bolsa()
    {
        return $this->hasMany(Bolsa::class, 'doacao_id', 'id');
    }
}
