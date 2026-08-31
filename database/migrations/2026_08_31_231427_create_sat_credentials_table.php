<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('nit', 20)->index();
            // Cifrado en reposo con el cast `encrypted`; el texto cifrado excede
            // holgadamente lo que cabe en un varchar corto, de ahí el text.
            $table->text('password');
            $table->string('environment', 20)->default('pruebas');
            $table->boolean('is_active')->default(true)->index();
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('secret_rotated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nit', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_credentials');
    }
};
