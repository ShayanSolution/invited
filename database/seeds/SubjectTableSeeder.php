<?php

use Illuminate\Database\Seeder;

class SubjectTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subjects')->insert([
            'name' => 'English',
            'status' => 1,
            'programme_id' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('subjects')->insert([
            'name' => 'Mathematics',
            'status' => 1,
            'programme_id' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);

        DB::table('subjects')->insert([
            'name' => 'Science',
            'status' => 1,
            'programme_id' => 1,
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
        ]);
        
        
    }
}
