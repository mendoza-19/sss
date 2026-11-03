<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('iscustomer')->default(true);
            $table->boolean('googlepassword')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        User::create([
            'name' => 'Admin',
            'email' => env('ADMIN_EMAIL'),
            'password' => Hash::make(env('ADMIN_PASSWORD')),
            'email_verified_at' => now(),
            'iscustomer' => false,
            'googlepassword' => false,
        ]);

        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('email');
            $table->integer('codigo');
            $table->foreignId('user_id')->constrained(
                table: 'users',
                column: 'id',
                indexName: 'email_change_requests_users_fk'
            )->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::disableForeignKeyConstraints();

    Schema::dropIfExists('sessions');
    Schema::dropIfExists('password_reset_tokens');
    Schema::dropIfExists('email_change_requests');
    Schema::dropIfExists('users');

    Schema::enableForeignKeyConstraints();
}
};
