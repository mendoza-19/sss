<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distrito', function (Blueprint $table) {
            $table->id('iddistrito')->autoIncrement();
            $table->string('nombre', 100);
            $table->unsignedBigInteger('idmunicipio');

            $table->foreign('idmunicipio')
                ->references('idmunicipio')
                ->on('municipio')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('distrito');

        Schema::enableForeignKeyConstraints();
    }
};
