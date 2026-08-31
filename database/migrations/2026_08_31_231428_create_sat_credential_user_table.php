<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_credential_user', function (Blueprint $table) {
            $table->id();
            // unique(): un usuario opera con una sola credencial. El sistema
            // legacy asumía esta regla pero solo la aplicaba en la interfaz.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('sat_credential_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_credential_user');
    }
};
