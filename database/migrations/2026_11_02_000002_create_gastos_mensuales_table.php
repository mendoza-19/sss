<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 20);
            $table->date('mes');
            $table->string('descripcion')->nullable();
            $table->decimal('monto', 10, 2);
            $table->timestamps();
            $table->index(['mes', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_mensuales');
    }
};