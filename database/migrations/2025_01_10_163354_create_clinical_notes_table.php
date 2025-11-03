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
        if (!Schema::hasTable('clinical_notes')) {
            Schema::create('clinical_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('note_id')->nullable()->index('clinical_notes_note_id_foreign');
                $table->unsignedBigInteger("appointment_summary_id")->nullable();
                $table->unsignedBigInteger('on_demand_smart_note_id')->nullable();
                // $table->foreign("appointment_summary_id")
                //   ->references("id")
                //   ->on("appointment_summaries");
                $table->text('subjective')->nullable();
                $table->text('chief_complaint')->nullable();
                $table->text('history_of_present_illness')->nullable();
                $table->text('current_medications')->nullable();
                $table->text('diagnosis')->nullable();
                $table->text('assessments')->nullable();
                $table->text('plan')->nullable();
                $table->string('resource')->nullable();
                $table->text('procedures')->nullable();
                $table->text('medications')->nullable();
                $table->text('risks_benefits_discussion')->nullable();
                $table->text('care_plan')->nullable();
                $table->text('next_follow_up')->nullable();
                $table->date('date')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable()->index('clinical_notes_doctor_id_foreign');
                $table->unsignedBigInteger('patient_id')->nullable()->index('clinical_notes_patient_id_foreign');
                $table->timestamps();
                $table->boolean('is_shared')->default(false);
                $table->integer('next_follow_up_value')->nullable();
                $table->string('next_follow_up_timeframe')->nullable();
                $table->softDeletes();
                
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_notes');
    }
};