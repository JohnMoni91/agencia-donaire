<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Modelo extends Model
{
    use HasFactory;

    /**
     * A lista de permissões FINAL. Contém todos os campos do seu formulário.
     */
    protected $fillable = [
        'user_id',
        'nome',
        'categoria',
        'altura_cm',
        'busto_torax',
        'cintura_cm',
        'quadril_cm',
        'manequim',
        'sapatos',
        'status',
        'feedback_admin',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Modelo $modelo) {
            foreach ($modelo->fotos as $foto) {
                Storage::disk('public')->delete($foto->caminho_foto);
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function fotos() { return $this->hasMany(Foto::class); }
    public function agenda() { return $this->hasMany(Agenda::class); }
}