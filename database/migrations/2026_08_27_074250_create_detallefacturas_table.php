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
        Schema::create('detallefacturas', function (Blueprint $table) {
            $table->id('iddetallefactura')->autoIncrement();
            $table->decimal('cantidad', 12, 3);
            $table->double('valorunitario');
            $table->date('fechavencimiento');
            $table->timestamps();
            $table->foreignId('factura_id')->nullable()->constrained(
                table: 'facturas',
                column: 'idfactura',
                indexName: 'detallefacturas_factura_fk'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('ingrediente_id')->nullable()->constrained(
                table: 'ingredientes',
                column: 'idingrediente',
                indexName: 'detallefacturas_ingrediente_fk'
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallefacturas');
    }
};
