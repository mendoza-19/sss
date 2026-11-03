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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('idcliente')->autoIncrement();
            $table->string('nombre', 60);
            $table->string('apellido', 60)->nullable();
            $table->string('telefono', 15)->nullable();
            $table->text('comment')->default('');
            $table->integer('assessment')->default(0);
            $table->date('fecharegistro');
            $table->text('foto')->nullable();
            $table->timestamps();
            $table->foreignid('user_id')->nullable()->constrained(
                table: 'users',
                column: 'id',
                indexName: 'cliente_users_fk'
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
