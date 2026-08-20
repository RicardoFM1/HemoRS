<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Endereco extends Model
{
    protected $table = 'endereco';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
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

    public function doador()
    {
        return $this->hasMany(Doador::class, 'endereco_id', 'id');
    }

    
    public function unidade()
    {
        return $this->hasMany(Unidade::class, 'endereco_id', 'id');
    }

}
