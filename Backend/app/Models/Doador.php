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
        'autorizacao_responsavel',
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
        return $this->hasMany(Doacao::class, 'doador_id', 'id');
    }

    
}
