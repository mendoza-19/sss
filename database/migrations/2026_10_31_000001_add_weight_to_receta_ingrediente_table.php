<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //Se comenta esta migracion por que la parte de peso y medida fue integrada en la migracion 2026_10_19_000000_create_receta_ingrediente_table.php
        /*Schema::table('receta_ingrediente', function (Blueprint $table) {
            $table->unsignedInteger('peso')->nullable();
            $table->string('medida', 2)->nullable();
        });*/
    }

    public function down(): void
    {
        /*Schema::table('receta_ingrediente', function (Blueprint $table) {
            $table->dropColumn(['peso', 'medida']);
        });*/
    }
};