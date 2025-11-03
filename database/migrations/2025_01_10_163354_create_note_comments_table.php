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
        if (!Schema::hasTable('note_comments')) {
            Schema::create('note_comments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('patient_id')->index('note_comments_patient_id_foreign');
                $table->unsignedBigInteger('user_id')->index('note_comments_user_id_foreign');
                $table->longText('body');
                $table->boolean('edited')->default(false);
                $table->timestamps();
                $table->unsignedBigInteger('internal_note_id')->nullable()->index('note_comments_internal_note_id_foreign');
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_comments');
    }
};