<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //migracion redundante, ya que se agregaron las columnas en la migracion de creacion de la tabla preparaciones
        /*Schema::table('preparaciones', function (Blueprint $table) {
            $table->unsignedInteger('unidades_planeadas')->nullable();
            $table->unsignedInteger('unidades_horneadas')->nullable();
            $table->unsignedInteger('unidades_quemadas')->default(0);
            $table->decimal('costo_total', 12, 2)->nullable();
        });*/
    }

    public function down(): void
    {
        /*Schema::table('preparaciones', function (Blueprint $table) {
            $table->dropColumn([
                'unidades_planeadas',
                'unidades_horneadas',
                'unidades_quemadas',
                'costo_total',
            ]);
        });*/
    }
};