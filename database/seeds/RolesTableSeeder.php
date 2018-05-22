<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            'name' => 'Admin',
            'status' => 1,
        ]);

        DB::table('roles')->insert([
            'name' => 'Tutor',
            'status' => 1,
        ]);

        DB::table('roles')->insert([
            'name' => 'Student',
            'status' => 1,
        ]);
    }
}
