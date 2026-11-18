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

        if (! Schema::hasColumn($table, 'uuid')) {
            Schema::table($table, fn (Blueprint $schema) => $schema->uuid('uuid')->nullable()->unique());
        }

        DB::table($table)->whereNull('uuid')->select('id')->eachById(function ($contact) use ($table) {
            DB::table($table)->where('id', $contact->id)->whereNull('uuid')->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        $table = config('contacts.table', 'contacts');
        Schema::table($table, fn (Blueprint $schema) => $schema->dropUnique(['uuid']));
        Schema::table($table, fn (Blueprint $schema) => $schema->dropColumn('uuid'));
    }
};
