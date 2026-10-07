<?php
namespace IlBronza\Contacts\Tests\Fixtures\Models;

use IlBronza\Contacts\Models\Traits\InteractsWithContact;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use InteractsWithContact;

    protected $table = 'owners';
    protected $guarded = [];
    public $timestamps = false;

    public function userCanSee() { return (bool) $this->can_view; }
    public function userCanUpdate() { return (bool) $this->can_update; }
    public function getShowUrl() { return '/owners/' . $this->getKey(); }
}
