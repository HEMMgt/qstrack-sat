<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_transactions', function (Blueprint $table) {
            $table->id();
            // Referencia corta que se le puede mostrar al usuario cuando algo
            // falla, para localizar la llamada exacta en el historial.
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('sat_credential_id')->nullable()->index()
                ->constrained('sat_credentials')->nullOnDelete();

            $table->string('endpoint', 40);
            $table->string('environment', 20);
            $table->string('base_url', 255);

            // Siempre con la contraseña redactada. Ver SatClient::redactPayload().
            $table->json('request_payload')->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->boolean('succeeded')->default(false)->index();

            $table->string('response_type', 40)->nullable();
            $table->text('response_description')->nullable();
            $table->json('response_manifiesto')->nullable();
            // Cuerpo crudo, truncado. Imprescindible cuando la SAT contesta HTML
            // o XML en vez del JSON prometido.
            $table->longText('response_raw')->nullable();

            $table->string('error_class', 120)->nullable();
            $table->text('error_message')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['endpoint', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_transactions');
    }
};
