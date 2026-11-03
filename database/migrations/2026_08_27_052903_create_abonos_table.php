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
        Schema::create('abonos', function (Blueprint $table) {
            $table->id('idabono');
            $table->date('fechaabono');
            $table->integer('cantidadabonada');
            $table->string('metodopago', 30);
            $table->timestamps();
            $table->foreignid('venta_id')->constrained(
                table: 'ventas',
                column: 'idventa',
                indexName: 'abonos_venta_fk'
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
