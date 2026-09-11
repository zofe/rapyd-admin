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
        if (Schema::hasTable('workflow_step_involved')) {
            return;
        }

        Schema::create('workflow_step_involved', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_step_id')
                ->constrained('workflow_steps')
                ->onDelete('cascade');

            $table->uuidMorphs('involved'); // => involved_type + involved_id (uuid)
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
        Schema::dropIfExists('workflow_step_involved');
    }
};
