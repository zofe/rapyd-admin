<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// state_code: ISO 3166-2 subdivision (US state, CA province…), needed by the tax
// rules of countries taxing by destination. country_code (ISO 3166-1) already exists.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'state_code')) {
                $table->string('state_code', 10)->nullable()->after('country_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'state_code')) {
                $table->dropColumn('state_code');
            }
        });
    }
};
