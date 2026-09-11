<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'tier')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('tier')->nullable()->index()->after('parent_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'tier')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropIndex(['tier']);
                $table->dropColumn('tier');
            });
        }
    }
};
