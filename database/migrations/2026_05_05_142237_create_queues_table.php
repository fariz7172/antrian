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
        Schema::create('queues', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('poli_id')->constrained('polis')->onDelete('cascade');
            $blueprint->integer('number'); // Nomor urut (1, 2, 3...)
            $blueprint->string('ticket_number'); // Full number (A-001)
            $blueprint->enum('status', ['waiting', 'calling', 'serving', 'finished', 'skipped'])->default('waiting');
            $blueprint->timestamp('called_at')->nullable();
            $blueprint->timestamp('finished_at')->nullable();
            $blueprint->timestamps();

            // Indexing for faster searching today's queues
            $blueprint->index(['poli_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
