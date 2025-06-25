<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $queryFilters = "CREATE TABLE filters (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL)";

        $queryFilterValues = "CREATE TABLE filter_values (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        value VARCHAR(255) NOT NULL ,
        filter_id INT NOT NULL ,
        FOREIGN KEY (filter_id) REFERENCES filters(id))";

        DB::unprepared($queryFilters);
        DB::unprepared($queryFilterValues);
    }

    public function down(): void
    {
        Schema::dropIfExists('filters');
    }
};
