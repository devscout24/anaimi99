<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_prices')) {
            return;
        }

        if (!Schema::hasColumn('service_prices', 'time_duration')) {
            Schema::table('service_prices', function (Blueprint $table) {
                $table->string('time_duration')->nullable()->after('discount');
            });
        }

        if (!Schema::hasColumn('service_prices', 'time')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("UPDATE service_prices SET time_duration = COALESCE(time_duration, DATE_FORMAT(`time`, '%H:%i')) WHERE `time` IS NOT NULL");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('UPDATE service_prices SET time_duration = COALESCE(time_duration, TO_CHAR("time", \'HH24:MI\')) WHERE "time" IS NOT NULL');
            return;
        }

        if ($driver === 'sqlite') {
            DB::statement('UPDATE service_prices SET time_duration = COALESCE(time_duration, strftime(\'%H:%M\', "time")) WHERE "time" IS NOT NULL');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('UPDATE service_prices SET time_duration = COALESCE(time_duration, FORMAT([time], \'HH:mm\')) WHERE [time] IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank to avoid dropping application data.
    }
};
