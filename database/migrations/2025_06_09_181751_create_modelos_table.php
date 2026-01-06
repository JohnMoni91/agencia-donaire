<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // dentro do arquivo ..._create_modelos_table.php
    public function up(): void
    {
        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('nome');
            $table->string('categoria'); // Usando 'categoria'
            
            // Colunas de medidas
            $table->integer('altura_cm')->nullable();
            $table->integer('busto_torax')->nullable();
            $table->integer('cintura_cm')->nullable();
            $table->integer('quadril_cm')->nullable();
            $table->string('manequim')->nullable();
            $table->string('sapatos')->nullable();

            // Colunas de status e feedback
            $table->enum('status', ['pendente', 'aprovado', 'reprovado'])->default('pendente');
            $table->text('feedback_admin')->nullable();
            
            $table->timestamps();
        });
    }

/**
     * Reverse the migrations.
     * VERSÃO CORRIGIDA: Desativa a checagem de chaves para permitir o drop.
     */
    public function down(): void
    {
        // Desativa temporariamente a checagem de chaves estrangeiras
        Schema::disableForeignKeyConstraints();

        // Apaga a tabela
        Schema::dropIfExists('modelos');

        // Reativa a checagem de chaves estrangeiras (boa prática)
        Schema::enableForeignKeyConstraints();
    }
};