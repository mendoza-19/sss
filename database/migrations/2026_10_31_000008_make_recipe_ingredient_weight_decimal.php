<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receta_ingrediente', function (Blueprint $table) {
            $table->decimal('peso', 12, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('receta_ingrediente', function (Blueprint $table) {
            $table->unsignedInteger('peso')->nullable()->change();
        });
    }
};