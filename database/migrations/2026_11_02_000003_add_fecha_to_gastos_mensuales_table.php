<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_mensuales', function (Blueprint $table) {
            $table->date('fecha')->nullable()->after('mes');
        });

        DB::table('gastos_mensuales')
            ->whereNull('fecha')
            ->update(['fecha' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('gastos_mensuales', function (Blueprint $table) {
            $table->dropColumn('fecha');
        });
    }
};