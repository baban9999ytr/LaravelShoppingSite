<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
    Course::create([
    'name' => 'Laravel',
    'description' => 'Laravel is a PHP Framework known for  its ability to build web applications in a convenient, flexible, and elegant way.',
    'image' => 'https://laravel.com/img/logomark.min.svg',
    'price' => '100',   
    ]);
Course::create([
    'name' => 'React Js',
    'description' => 'React js is known for  its ability to build web applications in a convenient, flexible, and elegant way.',
    'image' => 'https://react.dev/images/react-logo-200x200.png',
    'price' => '200',
    ]);Course::create([
    'name' => 'Vue Js',
    'description' => 'Description 2',
    'image' => 'https://vuejs.org/images/logo.svg',
    'price' => '200',   
    ]);


    }
}
