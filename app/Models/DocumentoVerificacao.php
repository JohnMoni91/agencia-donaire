<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoVerificacao extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'caminho_documento'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    
}