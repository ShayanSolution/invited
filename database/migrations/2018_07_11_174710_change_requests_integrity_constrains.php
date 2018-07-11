<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRequestsIntegrityConstrains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedInteger('event_id')->nullable(false)->change();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');

            $table->unsignedInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedInteger('request_to')->nullable(false)->change();
            $table->foreign('request_to')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
