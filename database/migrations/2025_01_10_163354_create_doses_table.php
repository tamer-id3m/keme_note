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
        if (!Schema::hasTable('doses')) {
            Schema::create('doses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->longText('directions');
                $table->unsignedBigInteger('medication_id')->index('doses_medication_id_foreign');
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doses');
    }
};