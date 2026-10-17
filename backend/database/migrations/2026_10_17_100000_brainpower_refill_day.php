<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Das Maximum rechnet der Server aus Level und Prestige (App\Support\Brainpower), gespeichert wird nur der Stand
     * und der Tag des letzten Aufladens (Kalendertag in Wien).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('brainpower_max');
            $table->date('brainpower_refilled_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('brainpower_refilled_on');
            $table->integer('brainpower_max')->default(100);
        });
    }
};
