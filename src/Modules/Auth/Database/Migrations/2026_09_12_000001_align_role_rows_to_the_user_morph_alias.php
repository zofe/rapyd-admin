<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Since 9.x the package registers the morph alias "user" for the auth model, so
// Spatie looks for model_type = "user". Rows written before that (model_type =
// the User FQCN) made every role and permission disappear after the upgrade.
return new class extends Migration {
    public function up(): void
    {
        $class = Relation::getMorphedModel('user');
        if (! $class) {
            return; // no alias registered: nothing to align
        }

        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('model_type', $class)->update(['model_type' => 'user']);
            }
        }
    }

    public function down(): void
    {
        // Rows are valid either way for the code that wrote them: nothing to undo.
    }
};
