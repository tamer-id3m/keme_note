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
        Schema::create('on_demand_smart_notes', function (Blueprint $table) {
            $table->id();
            $table->boolean('approved')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->longText('note');
            $table->date('approval_date');
            $table->unsignedBigInteger('ai_env_id');
            $table->unsignedBigInteger('ai_model_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('approved_by');

            // $table->foreign('ai_env_id')->references('id')->on('ai_envs')->onDelete('cascade');
            // $table->foreign('ai_model_id')->references('id')->on('ai_models')->onDelete('cascade');
            // $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('approved_by')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('on_demand_smart_notes');
    }
};