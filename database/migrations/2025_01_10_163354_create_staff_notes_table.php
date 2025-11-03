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
        if (!Schema::hasTable('staff_notes')) {
            Schema::create('staff_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->nullable();
                $table->text('body');
                $table->boolean('edited')->default(false);
                $table->timestamps();
                $table->unsignedBigInteger('user_id')->index('staff_notes_user_id_foreign');
                $table->unsignedBigInteger('patient_id')->index('staff_notes_patient_id_foreign');
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_notes');
    }
};