<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterValuesToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            DB::table('roles')->where('name', 'Admin')->update(['name'=>'Super Admin']);
            DB::table('roles')->where('name', 'Tutor')->update(['name'=>'corporate client']);
            DB::table('roles')->where('name', 'Student')->update(['name'=>'User']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            DB::table('roles')->where('name', 'Super Admin')->update(['name'=>'Admin']);
            DB::table('roles')->where('name', 'corporate client')->update(['name'=>'Tutor']);
            DB::table('roles')->where('name', 'User')->update(['name'=>'Student']);
        });
    }
}
