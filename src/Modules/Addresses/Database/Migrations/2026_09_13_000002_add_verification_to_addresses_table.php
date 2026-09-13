<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Filled when the address comes from a lookup service: which one, when, and how
// precise the match was (verified = building level, partial = street/city).
return new class extends Migration {
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'verified_by')) {
                $table->string('verified_by', 40)->nullable()->after('address_lon');
            }
            if (! Schema::hasColumn('addresses', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('addresses', 'confidence')) {
                $table->string('confidence', 20)->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            foreach (['confidence', 'verified_at', 'verified_by'] as $column) {
                if (Schema::hasColumn('addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
