<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_manifests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('sat_credential_id')->nullable()
                ->constrained('sat_credentials')->nullOnDelete();
            $table->foreignId('sat_transaction_id')->nullable()
                ->constrained('sat_transactions')->nullOnDelete();

            $table->string('numero_manifiesto_consultado', 60)->index();

            // Los doce campos del objeto `manifiesto`, todos texto y opcionales:
            // la SAT devuelve cadena vacía en los que no aplican y el formato de
            // las fechas no está documentado.
            $table->string('nombre_cuscar', 60)->nullable();
            $table->string('numero_manifiesto', 60)->nullable();
            $table->string('fecha_recepcion', 40)->nullable();
            $table->text('firma_electronica')->nullable();
            $table->string('tipo_mensaje', 60)->nullable();
            $table->string('funcion_mensaje', 60)->nullable();
            $table->string('estado', 60)->nullable();
            $table->string('estado_dictamen', 60)->nullable();
            $table->string('tipo_operacion', 60)->nullable();
            $table->string('empresa_transmisora', 120)->nullable();
            $table->string('numero_viaje_vuelo', 60)->nullable();
            $table->string('nombre_medio_transporte', 120)->nullable();

            $table->timestamp('queried_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_manifests');
    }
};
