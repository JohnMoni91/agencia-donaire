<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        // database/migrations/xxxx_xx_xx_xxxxxx_create_fotos_table.php
    // ..._create_fotos_table.php
    public function up(): void
    {
        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_id')->constrained()->onDelete('cascade');
            $table->string('caminho_foto');
            $table->timestamps();
        });
    }
    };
