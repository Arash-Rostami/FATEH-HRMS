<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileSeeder extends Seeder
{
    public function run()
    {
        $profiles = [ [
            'id' => 2,
            'personnel_id' => '16118',
            'image' => 'img/user/profiles/arashrostami_1748784289.png',
            'attachments' => '[]',
            'gender' => 'male',
            'employment_type' => 'fulltime',
            'marital_status' => 'single',
            'number_of_children' => 7,
            'employment_status' => 'working',
            'id_card_number' => '0064653064',
            'id_booklet_number' => '3964',
            'degree' => 'postgraduate',
            'field' => 'IT - Software Engineering',
            'birthdate' => '1981-09-03 00:00:00',
            'landline' => '02122573536',
            'cellphone' => '09122398772',
            'license_plate' => '18 ق 373 ایران 60',
            'zip_code' => '1634646365',
            'address' => "No 2, Royan Aly, Piroozi Aly\nKerman Str, Resalat Hwy, Seyed Khandan",
            'accessibility' => 'N/A',
            'department_id' => 'HR',
            'position' => 'senior',
            'insurance' => '21269304',
            'emergency_phone' => '0912335746',
            'emergency_relationship' => 'my friend',
            'start_date' => '2022-07-23',
            'end_date' => null,
            'work_experience' => '15+',
            'interests' => 'music, wine, coffee, online games, online toturials, trips, and ...',
            'favorite_colors' => null,
            'user_id' => 1,
            'created_at' => null,
            'updated_at' => '2025-11-26 06:00:59',
        ]];


        DB::table('profiles')->insert($profiles);
    }
}
