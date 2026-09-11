<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Installs created before v9.5 kept memberships in a company_user pivot.
// Move the primary (or only) membership onto users.company_id/company_role and drop it.
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'company_role')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('company_role')->nullable()->after('company_id'));
        }

        if (! Schema::hasTable('company_user')) {
            return;
        }

        $query = DB::table('company_user');
        if (Schema::hasColumn('company_user', 'is_primary')) {
            $query->orderByDesc('is_primary');
        }
        $hasRole = Schema::hasColumn('company_user', 'role');

        $done = [];
        foreach ($query->orderBy('id')->get() as $row) {
            if (isset($done[$row->user_id])) {
                continue;
            }
            $done[$row->user_id] = true;

            DB::table('users')->where('id', $row->user_id)->update([
                'company_id' => $row->company_id,
                'company_role' => $hasRole ? $row->role : 'member',
            ]);
        }

        Schema::dropIfExists('company_user');
    }

    public function down(): void
    {
        // Memberships are not restored: the pivot is gone for good.
    }
};
