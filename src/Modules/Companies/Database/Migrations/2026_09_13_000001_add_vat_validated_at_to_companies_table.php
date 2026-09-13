<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// When the VAT number was last confirmed by VIES (null = never or invalid).
return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'vat_validated_at')) {
                $table->dateTime('vat_validated_at')->nullable()->after('vat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'vat_validated_at')) {
                $table->dropColumn('vat_validated_at');
            }
        });
    }
};
