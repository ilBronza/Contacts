<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('contacts.models.contact.table'), function (Blueprint $table) {
            $table->string('email_verification_token_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table(config('contacts.models.contact.table'), function (Blueprint $table) {
            $table->dropColumn('email_verification_token_hash');
        });
    }
};
