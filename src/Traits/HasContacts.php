<?php

namespace Whilesmart\Contacts\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Whilesmart\Contacts\Models\Contact;

trait HasContacts
{
    public function contacts(): MorphMany
    {
        return $this->morphMany(config('contacts.model', Contact::class), 'contactable');
    }

    public function primaryContact(): MorphOne
    {
        return $this->morphOne(config('contacts.model', Contact::class), 'contactable')
            ->where('is_primary', true);
    }
}
