<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filament counts unread notifications with data->>'format'. PostgreSQL only allows that operator on json or jsonb.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $column = DB::selectOne("select data_type from information_schema.columns where table_schema = current_schema() and table_name = 'notifications' and column_name = 'data'");

        if ($column === null || in_array($column->data_type, ['json', 'jsonb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
    }
};
