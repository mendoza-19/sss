<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipio', function (Blueprint $table) {
            $table->id('idmunicipio')->autoIncrement();
            $table->string('nombre', 100);
            $table->unsignedBigInteger('iddepartamento');

            $table->foreign('iddepartamento')
                ->references('iddepartamento')
                ->on('departamento')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipio');
    }
};