<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $pdo = DB::getPdo();
        $pdo->query("INSERT INTO offers (available, name, price, description, vendor, vendor_code, barcode) VALUES
        (1, 'Offer 1', 100, 'Description for offer 1', 'Vendor A', 'VCODE123', 3349610010274),
        (0, 'Offer 2', 200, 'Description for offer 2', 'Vendor B', 'VCODE456', 3349610010274),
        (1, 'Offer 3', 150, 'Description for offer 3', 'Vendor C', 'VCODE789', 3349610010274),
        (1, 'Offer 4', 150, 'Description for offer 4', 'Vendor C', 'VCODE789', 3349610010274),
        (1, 'Offer 5', 150, 'Description for offer 5', 'Vendor C', 'VCODE789', 3349610010274),
        (1, 'Offer 6', 150, 'Description for offer 6', 'Vendor C', 'VCODE789', 3349610010274),
        (1, 'Offer 7', 150, 'Description for offer 7', 'Vendor C', 'VCODE789', 3349610010274),
        (1, 'Offer 8', 150, 'Description for offer 8', 'Vendor C', 'VCODE789', 3349610010274)");

        $pdo->query("INSERT INTO filters (name, slug) VALUES
        ('Brand', 'brand'),
        ('Color', 'color'),
        ('Size', 'size')");

        $pdo->query("INSERT INTO filter_values (value, filter_id) VALUES
        ('Apple', 1),   
        ('Samsung', 1), 
        ('Red', 2),  
        ('Blue', 2), 
        ('Small', 3),
        ('Large', 3)");

    }
}
