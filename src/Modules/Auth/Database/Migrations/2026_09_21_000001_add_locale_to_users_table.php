<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The language a user chose with the switcher (SetLocale middleware); null = not chosen yet. */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'locale')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'locale')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('locale'));
        }
    }
};
