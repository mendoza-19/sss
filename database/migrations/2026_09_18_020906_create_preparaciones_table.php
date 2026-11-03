<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained(
                table: 'recetas',
                column: 'idreceta'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->date('fecha');
            $table->unsignedInteger('unidades_producidas')->nullable();
            $table->unsignedInteger('unidades_planeadas')->nullable();
            $table->unsignedInteger('unidades_horneadas')->nullable();
            $table->unsignedInteger('unidades_quemadas')->default(0);
            $table->decimal('costo_total', 12, 2)->nullable();
            $table->decimal('libras_planeadas', 12, 3)->nullable();
            $table->unsignedInteger('latas_horneadas')->nullable();
            $table->json('detalle_latas')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('preparaciones')) {
            Schema::dropIfExists('preparaciones');
        }
    }
};
