<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bolsa extends Model
{
    protected $table = 'bolsa';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'doacao_id',
        'codigo',
        'tipo_sanguineo',
        'coletado_em',
        'vence_em',
        'status'
    ];

    public function doacao()
    {
        return $this->belongsTo(Doacao::class, 'doacao_id', 'id');
    }
}
