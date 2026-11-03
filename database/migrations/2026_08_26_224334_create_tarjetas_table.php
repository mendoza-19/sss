<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tarjetas', function (Blueprint $table) {
            $table->id('codigo');
            $table->date('caducidad');
            $table->string('tipo', 30);
            $table->integer('clave');
            $table->timestamps();
            $table->foreignid('cliente_id')->constrained(
                table: 'clientes',
                column: 'idcliente',
                indexName: 'tarjeta_cliente_fk',
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarjetas');
    }
};
