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
        'cidade',
        'capacidade_diaria',
        'longitude',
        'latitude'
       
    ];

    public function doacao()
    {
        return $this->hasMany(Doacao::class, 'unidade_id', 'id');
    }

}
