<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('productos')
            ->whereIn('nombre', [
                'Pan Artesanal',
                'Baguette Rústica',
                'Buñuelo Glaseado',
                'Croissant de Mantequilla',
                'Concha de Chocolate',
                'Pastel de Tres Leches',
            ])
            ->delete();
    }

    public function down(): void
    {
    }
};