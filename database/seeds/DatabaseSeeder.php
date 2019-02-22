<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name'     => 'Aam Surganda',
            'email'    => 'surganda@gmail.com',
            'password' => Hash::make('123456')
        ]);
    }
}
