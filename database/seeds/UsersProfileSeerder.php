<?php

use Illuminate\Database\Seeder;

class UsersProfileSeerder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\Profile::class, 10)->create();
    }
}
