<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id('idempleado');
            $table->unsignedBigInteger('idusuario');
            $table->unsignedBigInteger('idrol');
            $table->string('duiempleado', 20)->nullable();
            $table->string('nombreCompleto', 60)->nullable();
            $table->string('telefono', 15)->nullable();
            $table->date('iniciocontrato')->nullable();
            $table->string('estado', 20)->default('Activo');
            $table->unsignedBigInteger('iddistrito')->nullable();
            $table->text('Residencia')->nullable();
            $table->binary('foto')->nullable();
            $table->timestamps();

            $table->foreign('idusuario')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('idrol')
                ->references('idrol')
                ->on('rol')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('iddistrito')
                ->references('iddistrito')
                ->on('distrito')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE empleados MODIFY foto LONGBLOB NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
