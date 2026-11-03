<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('detallefacturas', 'medida')) {
            Schema::table('detallefacturas', function (Blueprint $table) {
                $table->dropColumn('medida');
            });
        }
    }

    public function down(): void
    {
    }
};