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
        if (!Schema::hasTable('ai_notes')) {
            Schema::create('ai_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('patient_id')->nullable()->index('ai_notes_patient_id_foreign');
                $table->unsignedBigInteger('doctor_id')->nullable()->index('ai_notes_doctor_id_foreign');
                $table->boolean('approved')->default(false);
                $table->boolean('is_shared')->default(false);
                $table->longText('note');
                $table->date('approval_date');
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('approved_by')->nullable()->index('ai_notes_approved_by_foreign');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_notes');
    }
};