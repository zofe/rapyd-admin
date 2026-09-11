<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->string('role')->default('member')->after('user_id');
            $table->boolean('is_primary')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_primary']);
        });
    }
};
