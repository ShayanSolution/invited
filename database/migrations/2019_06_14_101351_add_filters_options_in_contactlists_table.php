<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFiltersOptionsInContactlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contactlists', function (Blueprint $table) {
            $table->string('location_filter')->nullable();
            $table->string('gender_filter')->nullable();
            $table->string('anniversary_filter')->nullable();
            $table->string('date_of_birth_filter')->nullable();
            $table->string('age_range_filter')->nullable();
            $table->string('active_user_filter')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contactlists', function (Blueprint $table) {
            $table->dropColumn('location_filter');
            $table->dropColumn('gender_filter');
            $table->dropColumn('anniversary_filter');
            $table->dropColumn('date_of_birth_filter');
            $table->dropColumn('age_range_filter');
            $table->dropColumn('active_user_filter');
        });
    }
}
