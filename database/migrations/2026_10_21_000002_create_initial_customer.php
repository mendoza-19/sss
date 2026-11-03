<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CUSTOMER_EMAIL = 'cliente@example.com';

    public function up(): void
    {
        if (DB::table('users')->where('email', self::CUSTOMER_EMAIL)->exists()) {
            return;
        }

        $userId = DB::table('users')->insertGetId([
            'name' => 'Cliente 1',
            'email' => self::CUSTOMER_EMAIL,
            'email_verified_at' => now(),
            'password' => '$2y$12$MaYPEn/luqKelHDS60MXYuXgoyRw6Yyw19fZin9Z2CwJzYjln0gzW',
            'iscustomer' => true,
            'googlepassword' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('clientes')->insert([
            'nombre' => 'Cliente',
            'apellido' => '1',
            'telefono' => '7000-0000',
            'fecharegistro' => today(),
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $userId = DB::table('users')
            ->where('email', self::CUSTOMER_EMAIL)
            ->value('id');

        if ($userId) {
            DB::table('clientes')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        }
    }
};
