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
        Schema::create('productos', function (Blueprint $table) {
            $table->id('idproducto')->autoIncrement();
            $table->string('nombre', 60);
            $table->string('estado', 30);
            $table->boolean('visible')->default(true);
            $table->integer('stock');
            $table->text('foto');
            $table->text('descripcion');
            $table->decimal('precio', 10, 2)->default(0);
            $table->decimal('costo', 10, 2)->default(0);
            $table->timestamps();
            $table->foreignid('receta_id')->nullable()->constrained(
                table: 'recetas',
                column: 'idreceta',
                indexName: 'receta_productos_fk'
            )->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
