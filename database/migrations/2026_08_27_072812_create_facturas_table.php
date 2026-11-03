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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id('idfactura')->autoIncrement();
            $table->date('emicion');
            $table->date('fechapago');
            $table->double('total');
            $table->text('foto')->nullable();
            $table->timestamps();
            $table->foreignid('proveedor_id')->constrained(
                table: 'proveedors',
                column: 'idproveedor',
                indexName: 'fk_proveedor_factura'
            )->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
