<?php

use Illuminate\Database\Seeder;

class ProgramesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('programmes')->insert([
            'name' => 'Matriculation',
            'status' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('programmes')->insert([
            'name' => 'ICS',
            'status' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('programmes')->insert([
            'name' => 'BS(CS)',
            'status' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
    }
}
