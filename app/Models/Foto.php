<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foto extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'caminho_foto', // A coluna que o erro pediu
        'modelo_id',    // A coluna que conecta com o modelo
    ];

    /**
     * Uma foto pertence a um modelo.
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }
}