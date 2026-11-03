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
        Schema::create('proporcionesrecetas', function (Blueprint $table) {
            $table->id('idproporciones');
            $table->decimal('unidades', 12, 3);
            $table->string('medida', 30)->nullable();
            $table->timestamps();
            $table->foreignId('preparacion_id')->nullable()->constrained(
                table: 'preparaciones',
                column: 'id'
            )->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('ingrediente_id')->nullable()->constrained(
                table: 'ingredientes',
                column: 'idingrediente',
                indexName: 'proporciones_ingrediente_fk'
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proporcionesrecetas');
    }
};
