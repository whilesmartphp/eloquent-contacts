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

        $column = collect(Schema::getColumns($table))->firstWhere('name', 'slug');
        if ($column === null) {
            Schema::table($table, fn (Blueprint $schema) => $schema->uuid('slug')->nullable()->unique());
        } elseif (! $column['nullable']) {
            return;
        }

        DB::table($table)->whereNull('slug')->select('id')->eachById(function ($contact) use ($table) {
            DB::table($table)->where('id', $contact->id)->whereNull('slug')->update(['slug' => (string) Str::uuid()]);
        });

        Schema::table($table, fn (Blueprint $schema) => $schema->uuid('slug')->nullable(false)->change());
    }
};
