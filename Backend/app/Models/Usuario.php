<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model{
    protected $table = 'usuario';
    protected $primaryKey = 'id';
    public $timestamps = false;


    public $fillable = [
        'nome',
        'email',
        'senha',
        'perfil',
        'status'
    ];

    public function doacao () {
        return $this->hasMany(Doacao::class, 'usuario_id', 'id');
    }

    public function doacao_historico () {
        return $this->hasMany(Doacao_historico::class, 'usuario_id', 'id');
    }
}