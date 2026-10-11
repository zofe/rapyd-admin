<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_records', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64);
            $table->string('resource', 64);
            $table->string('remote_id', 191);
            $table->string('label')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fetched_at');
            $table->unique(['source', 'resource', 'remote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_records');
    }
};
