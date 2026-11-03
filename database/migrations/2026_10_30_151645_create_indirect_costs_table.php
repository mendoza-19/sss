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
        Schema::create('indirect_costs', function (Blueprint $table) {
            $table->id('idindirect_cost')->autoIncrement();
            $table->string('reason', 255);
            $table->integer('value');
            $table->timestamps();
            $table->foreignId('productos_id')->constrained(
                table: 'productos',
                column: 'idproducto',
                indexName: 'indirect_costs_products'
            )->onDelete('cascade')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indirect_costs');
    }
};
