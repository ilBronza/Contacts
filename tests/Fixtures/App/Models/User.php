<?php
namespace App\Models;

class User extends \Illuminate\Foundation\Auth\User
{
    use \Spatie\Permission\Traits\HasRoles;
    protected $guarded = [];
}
