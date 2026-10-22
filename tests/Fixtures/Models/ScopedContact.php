<?php

namespace IlBronza\Contacts\Tests\Fixtures\Models;

use IlBronza\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

class ScopedContact extends Contact
{
    protected static function booted(): void
    {
        static::addGlobalScope('visible', function (Builder $query) {
            $query->where('contact', 'visible@example.com');
        });
    }
}
