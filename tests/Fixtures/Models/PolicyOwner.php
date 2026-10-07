<?php
namespace IlBronza\Contacts\Tests\Fixtures\Models;

use IlBronza\Contacts\Models\Traits\InteractsWithContact;
use Illuminate\Database\Eloquent\Model;

class PolicyOwner extends Model
{
    use InteractsWithContact;
    protected $table = 'owners';
    protected $guarded = [];
    public $timestamps = false;
}
