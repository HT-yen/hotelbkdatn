<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'username' => "user",
            'full_name' => 'user test',
            'email' => 'user'.'@gmail.com',
            'phone' => '0976 876 554',
            'password' => bcrypt('1111'),
            'is_admin' => 0,
            'is_active' => true,
        ]);

        DB::table('users')->insert([
            'username' => "hotelier",
            'full_name' => 'hotelier test',
            'email' => 'hotelier'.'@gmail.com',
            'phone' => '0976 876 764',
            'password' => bcrypt('1111'),
            'is_admin' => 1,
            'is_active' => true,
        ]);

        DB::table('users')->insert([
            'username' => "admin",
            'full_name' => 'admin test',
            'email' => 'admin'.'@gmail.com',
            'phone' => '0976 876 869',
            'password' => bcrypt('1111'),
            'is_admin' => 2,
            'is_active' => true,
        ]);

        Model::unguard();
        factory(App\Model\User::class, 15)->create();
        Model::reguard();
    }
}
