<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        // database/migrations/xxxx_xx_xx_xxxxxx_create_agendas_table.php
        public function up(): void
        {
            Schema::create('agendas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('modelo_id')->constrained()->onDelete('cascade'); // Link com o perfil da modelo
                $table->date('data_indisponivel');
                $table->timestamps();
            });
        }
};
