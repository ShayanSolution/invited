<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Sessions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('student_id')->unsigned();
            $table->integer('tutor_id')->unsigned();
            $table->integer('programme_id')->unsigned();
            $table->integer('subject_id')->unsigned();
            $table->integer('subscription_id')->unsigned();
            $table->integer('meeting_type_id')->unsigned();
            $table->boolean('is_group');
            $table->integer('group_members');
            $table->enum('status', ['booked', 'started', 'ended']);
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            $table->integer('duration');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
