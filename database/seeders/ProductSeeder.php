<?php

namespace Database\Seeders;
use App\Models\Products;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        // data
        $data = [
            ['product_name' => 'Gold coffee', 'product_profit_margin' => '0.25',],
            ['product_name' => 'Arabic coffee', 'product_profit_margin' => '0.15',],
            // Add more rows as needed
        ];

         // Insert data into the table
         DB::table('products')->insert($data);
    }
}
