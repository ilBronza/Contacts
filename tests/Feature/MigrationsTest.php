<?php
namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationsTest extends TestCase
{
    public function test_migrations_can_be_rolled_back_and_reapplied(): void
    {
        $files = glob(__DIR__ . '/../../database/migrations/*.php');
        foreach (array_reverse($files) as $file)
            (require $file)->down();
        $this->assertFalse(Schema::hasTable('contacts__contacts'));
        $this->assertFalse(Schema::hasTable('contacts__contacttypes'));
        foreach ($files as $file)
            (require $file)->up();
        $this->assertTrue(Schema::hasColumns('contacts__contacttypes', ['href_string', 'fa_icon']));
        $this->assertTrue(Schema::hasTable('contacts__contacts'));
    }
}
