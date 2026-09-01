<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuscar_files', function (Blueprint $table) {
            // Emisor declarado en el segmento UNB del archivo. La SAT exige que
            // corresponda a la empresa con cuyas credenciales se transmite.
            $table->string('emisor', 35)->nullable()->index()->after('julian_extension');
            $table->string('numero_manifiesto_declarado', 60)->nullable()->after('emisor');
        });

        Schema::table('sat_credentials', function (Blueprint $table) {
            // Código de emisor que la SAT asocia a la empresa; aparece como
            // "Emisor (GLN)" en sus manifiestos. Opcional: mientras no se
            // capture, la credencial no restringe qué archivos se transmiten.
            $table->string('gln', 35)->nullable()->after('nit');
        });
    }

    public function down(): void
    {
        Schema::table('cuscar_files', function (Blueprint $table) {
            $table->dropColumn(['emisor', 'numero_manifiesto_declarado']);
        });

        Schema::table('sat_credentials', function (Blueprint $table) {
            $table->dropColumn('gln');
        });
    }
};
