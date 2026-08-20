<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doador extends Model
{
    protected $table = 'doador';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'nome',
        'cpf',
        'data_de_nascimento',
        'sexo',
        'tipo_sanguineo',
        'telefone',
        'email',
        'status',
        'endereco_id',
        'autorizacao_responsavel'
    ];

    public function doacao()
    {
        return $this->hasMany(Doacao::class, 'doador_id', 'id');
    }

    public function endereco() {
        return $this->belongsTo(Endereco::class, 'endereco_id', 'id');
    }
    
}
