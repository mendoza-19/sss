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
        Schema::create('ofertas', function (Blueprint $table) {
            $table->id('idoferta')->autoIncrement();
            $table->integer('descuento');
            $table->date('fechainicio');
            $table->date('fechafin');
            $table->timestamps();
            $table->foreignid('cliente_id')->constrained(
                table: 'clientes',
                column: 'idcliente',
                indexName: 'ofertas_cliente_fk'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignid('producto')->constrained(
                table: 'productos',
                column: 'idproducto',
                indexName: 'ofertas_producto_fk'
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas');
    }
};
