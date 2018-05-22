<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Constrains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('restrict');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreign('programme_id')
                ->references('id')->on('programmes')
                ->onDelete('cascade');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('meeting_type_id')
                ->references('id')->on('meeting_types')
                ->onDelete('restrict');
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreign('session_id')
                ->references('id')->on('sessions')
                ->onDelete('cascade');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('session_id')
                ->references('id')->on('sessions')
                ->onDelete('restrict');

            $table->foreign('subscription_id')
                ->references('id')->on('subscriptions')
                ->onDelete('restrict');

            $table->foreign('transaction_id')
                ->references('id')->on('transactions')
                ->onDelete('restrict');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('student_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->foreign('tutor_id')
                ->references('id')->on('users')
                ->onDelete('restrict');

            $table->foreign('programme_id')
                ->references('id')->on('programmes')
                ->onDelete('restrict');

            $table->foreign('subject_id')
                ->references('id')->on('subjects')
                ->onDelete('restrict');

            $table->foreign('subscription_id')
                ->references('id')->on('subscriptions')
                ->onDelete('restrict');

            $table->foreign('meeting_type_id')
                ->references('id')->on('meeting_types')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['programme_id']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['meeting_type_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['session_id',]);
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['transaction_id']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['tutor_id']);
            $table->dropForeign(['programme_id']);
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['meeting_type_id']);
        });
    }
}
