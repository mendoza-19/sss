<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ingredientes')->update(['stock' => 0]);
    }

    public function down(): void
    {
    }
};