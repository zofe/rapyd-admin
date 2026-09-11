<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('workflow_steps')) {
            return;
        }

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();

            $table->uuid('user_id')->nullable();
            $table->uuid('company_id')->nullable();

            $table->nullableUuidMorphs('workflowable');

            $table->json('places')->nullable();
            $table->json('places_from')->nullable();
            $table->string('last_transition')->nullable();

            $table->dateTime('transition_date')->nullable();
            $table->dateTime('scheduled_date')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflow_steps');
    }
};
