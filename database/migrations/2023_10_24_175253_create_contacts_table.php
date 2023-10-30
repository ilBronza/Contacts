<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('contacts.models.contacttype.table'), function (Blueprint $table) {
            $table->string('slug', 191)->primary();

            $table->string('name');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create(config('contacts.models.contact.table'), function (Blueprint $table) {
            $table->id();

            $table->morphs('contactable');
            $table->string('contact');

            $table->string('contacttype_slug', 191)->nullable();
            $table->foreign('contacttype_slug')->references('slug')->on(config('contacts.models.contacttype.table'));

            $table->unsignedBigInteger('sorting_index')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('contacts.models.contact.table'));
        Schema::dropIfExists(config('contacts.models.contacttype.table'));
    }
};
