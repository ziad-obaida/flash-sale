<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            'name' => 'Flash Sale Product',
            'price_cents' => 1999, // eg 19.99$
            'stock' => 10, // لتجربة الـ flash sale
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
