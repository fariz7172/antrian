<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('polis', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name'); // Nama Poli (e.g., Poli Umum)
            $blueprint->string('code', 5)->unique(); // Kode Awalan (e.g., A, B)
            $blueprint->string('description')->nullable();
            $blueprint->string('icon')->default('fas fa-hospital'); // FontAwesome icon
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polis');
    }
};
