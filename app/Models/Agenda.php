<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'data_indisponivel', // O campo que o erro pediu
        'modelo_id',         // A chave estrangeira que conecta ao modelo
    ];

    /**
     * Um registro da agenda pertence a um modelo.
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }
}