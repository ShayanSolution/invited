<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOneOnOneCallTutorToProfiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('profiles', function($table) {
            $table->integer('one_on_one')->after('is_deserving');
            $table->integer('call_tutor')->after('one_on_one');
            $table->integer('call_student')->after('call_tutor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('profiles', function($table) {
            $table->dropColumn('one_on_one');
            $table->dropColumn('call_tutor');
            $table->dropColumn('call_student');
        });
    }
}
