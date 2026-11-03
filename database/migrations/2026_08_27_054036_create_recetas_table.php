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
        Schema::create('recetas', function (Blueprint $table) {
            $table->id('idreceta')->autoIncrement();
            $table->string('nombre', 60);
            $table->text('descripcion');
            $table->text('instructivo');
            $table->decimal('peso', 10, 2)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('preparaciones')) {
            Schema::dropIfExists('preparaciones');
        }

        Schema::dropIfExists('recetas');
    }
};
