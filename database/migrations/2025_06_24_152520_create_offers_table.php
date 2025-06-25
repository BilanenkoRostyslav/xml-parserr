<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $query = "CREATE TABLE offers (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        available BOOLEAN NOT NULL DEFAULT FALSE,
        name VARCHAR(255),
        price INT NOT NULL,
        description TEXT NOT NULL,
        vendor VARCHAR(255),
        vendor_code VARCHAR(255),
        barcode VARCHAR(255) NOT NULL)";
        DB::unprepared($query);
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
