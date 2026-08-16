<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable()->index();
            $table->string('tier')->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('business_name');
            $table->string('status')->default('inactive');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('vat', 15)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('website')->nullable();
            $table->dateTime('registration_date')->nullable();
            $table->dateTime('activation_date')->nullable();
            $table->longText('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
