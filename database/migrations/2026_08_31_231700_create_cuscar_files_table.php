<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuscar_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('sat_credential_id')->constrained('sat_credentials')->restrictOnDelete();

            // Nombre lógico que se envía a la SAT: TCCCNNNN.JJJ, 12 caracteres.
            $table->string('filename', 12);
            $table->char('service_type', 1);
            $table->string('correlativo', 4);
            $table->char('julian_extension', 3);

            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64)->index();
            // Ruta dentro del disco privado `cuscar`, nunca bajo public/.
            $table->string('storage_path', 255);

            $table->string('status', 20)->default('cargado')->index();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sat_transaction_id')->nullable()
                ->constrained('sat_transactions')->nullOnDelete();

            $table->string('numero_manifiesto', 60)->nullable()->index();
            $table->string('fecha_recepcion', 40)->nullable();
            $table->text('firma_electronica')->nullable();
            $table->text('last_response_description')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuscar_files');
    }
};
