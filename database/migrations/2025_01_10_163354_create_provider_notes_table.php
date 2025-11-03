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
        if (!Schema::hasTable('provider_notes')) {
            Schema::create('provider_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->nullable()->unique();
                $table->unsignedBigInteger('doctor_id')->nullable()->index('provider_notes_doctor_id_foreign');
                $table->unsignedBigInteger('user_id')->nullable()->index('provider_notes_user_id_foreign');
                $table->longText('body');
                $table->boolean('edited')->default(false);
                $table->timestamps();
                $table->string('assignees')->nullable();
                $table->unsignedBigInteger('patient_id')->nullable()->index('provider_notes_patient_id_foreign');
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_notes');
    }
};