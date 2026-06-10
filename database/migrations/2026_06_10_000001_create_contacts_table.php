<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $uuids = (bool) config('contacts.uuids', false);

        Schema::create(config('contacts.table', 'contacts'), function (Blueprint $table) use ($uuids) {
            if ($uuids) {
                $table->uuid('id')->primary();
                $table->uuidMorphs('owner');
                $table->nullableUuidMorphs('contactable');
            } else {
                $table->id();
                $table->morphs('owner');
                $table->nullableMorphs('contactable');
            }

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contactable_type', 'contactable_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('contacts.table', 'contacts'));
    }
};
