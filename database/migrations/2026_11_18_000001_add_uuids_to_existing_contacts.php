<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('contacts.table', 'contacts');

        $column = collect(Schema::getColumns($table))->firstWhere('name', 'uuid');
        if ($column === null) {
            Schema::table($table, fn (Blueprint $schema) => $schema->uuid('uuid')->nullable()->unique());
        } elseif (! $column['nullable']) {
            return;
        }

        DB::table($table)->whereNull('uuid')->select('id')->eachById(function ($contact) use ($table) {
            DB::table($table)->where('id', $contact->id)->whereNull('uuid')->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table($table, fn (Blueprint $schema) => $schema->uuid('uuid')->nullable(false)->change());
    }

    public function down(): void
    {
        $table = config('contacts.table', 'contacts');
        Schema::table($table, fn (Blueprint $schema) => $schema->dropUnique(['uuid']));
        Schema::table($table, fn (Blueprint $schema) => $schema->dropColumn('uuid'));
    }
};
