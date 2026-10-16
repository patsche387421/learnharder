<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konten für M4a: Schüler:innen ohne Namen (E-025), Einwilligung, Sperren (E-027) und letzter Login für die Frist (E-028).
     * first_name und last_name wären Klarnamen auf Vorrat und werden nirgends gelesen.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->dropColumn(['first_name', 'last_name']);

            $table->timestamp('consented_at')->nullable();
            $table->string('terms_version')->nullable();
            $table->timestamp('parental_consent_at')->nullable();
            $table->foreignId('parental_consent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deactivation_reason')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        // name war Pflicht: Konten ohne Namen bekommen den Gamer-Tag oder die E-Mail
        DB::table('users')->whereNull('name')->update(['name' => DB::raw('coalesce(gamer_tag, email)')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parental_consent_by');
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropColumn(['consented_at', 'terms_version', 'parental_consent_at', 'deactivation_reason', 'last_login_at']);

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->nullable(false)->change();
        });
    }
};
