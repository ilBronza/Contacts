<?php
namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Support\Facades\DB;
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
        $this->assertTrue(Schema::hasColumn('contacts__contacts', 'existence_verified'));
        $this->assertTrue(Schema::hasColumn('contacts__contacts', 'email_verification_token_hash'));
    }

    public function test_existing_contacts_start_unverified_when_the_column_is_added(): void
    {
        $migration = require __DIR__ . '/../../database/migrations/2026_10_19_000000_add_existence_verified_to_contacts_table.php';
        $migration->down();
        $table = config('contacts.models.contact.table');
        $id = DB::table($table)->insertGetId([
            'contact' => 'legacy@example.com',
            'contactable_type' => 'owner',
            'contactable_id' => 1,
        ]);

        $migration->up();

        $this->assertSame(0, (int) DB::table($table)->where('id', $id)->value('existence_verified'));
        $this->assertSame('legacy@example.com', DB::table($table)->where('id', $id)->value('contact'));
    }
}
