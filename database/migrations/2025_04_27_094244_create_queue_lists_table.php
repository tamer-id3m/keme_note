<?php

use App\Enums\QueueStatus;
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
        Schema::create('queue_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('note_id')->nullable();
            $table->string(column: 'type')->nullable();
            $table->unsignedBigInteger(column: 'user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_lists');
    }
};