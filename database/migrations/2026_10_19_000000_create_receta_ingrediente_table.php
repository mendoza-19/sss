<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receta_ingrediente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained(
                table: 'recetas',
                column: 'idreceta'
            )->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('idingrediente');
            //Esta parte fue integrada como una sola migraion por razones de que la migracion 2026_10_31_000001_add_weight_to_receta_ingrediente_table.php no se podia ejecutar por que contradecia la migracion 2026_10_19_000000_create_receta_ingrediente_table.php
            $table->unsignedInteger('peso')->nullable();
            $table->string('medida', 2)->nullable();
            /*$table->decimal('peso', 12, 3)->nullable();
            $table->string('medida', 2)->nullable();*/

            $table->foreign('idingrediente', 'receta_ingrediente_ingrediente_fk')
                ->references('idingrediente')
                ->on('ingredientes')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receta_ingrediente');
    }
};
