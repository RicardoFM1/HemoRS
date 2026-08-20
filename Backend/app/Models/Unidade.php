<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unidade extends Model
{
    protected $table = 'unidade';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'nome',
        'endereco_id',
        'capacidade_diaria',
        'longitude',
        'latitude',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade', 
        'uf', 
        'latitude',
        'longitude',
        'endereco_origem'
       
    ];

    public function doacao()
    {
        return $this->hasMany(Doacao::class, 'unidade_id', 'id');
    }

    public function endereco()
    {
        return $this->belongsTo(Endereco::class, 'endereco_id', 'id');
    }

}
