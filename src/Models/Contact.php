<?php

namespace Whilesmart\Contacts\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Whilesmart\Contacts\Database\Factories\ContactFactory;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim(($this->first_name ?? '').' '.($this->last_name ?? '')));
    }

    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            if (config('contacts.uuids', false) && empty($contact->{$contact->getKeyName()})) {
                $contact->{$contact->getKeyName()} = (string) Str::orderedUuid();
            }
        });

        // Only one primary contact per contactable: demote the others.
        static::saving(function (Contact $contact) {
            if ($contact->is_primary && $contact->contactable_type && $contact->contactable_id) {
                static::query()
                    ->where('contactable_type', $contact->contactable_type)
                    ->where('contactable_id', $contact->contactable_id)
                    ->when($contact->exists, fn ($q) => $q->whereKeyNot($contact->getKey()))
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function getIncrementing(): bool
    {
        return ! (bool) config('contacts.uuids', false);
    }

    public function getKeyType(): string
    {
        return config('contacts.uuids', false) ? 'string' : 'int';
    }

    public function getTable(): string
    {
        return config('contacts.table', 'contacts');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }
}
