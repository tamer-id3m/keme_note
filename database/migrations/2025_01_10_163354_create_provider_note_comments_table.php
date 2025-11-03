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
        if (!Schema::hasTable('provider_note_comments')) {
            Schema::create('provider_note_comments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('uuid', 36)->nullable()->unique();
                $table->unsignedBigInteger('patient_id')->index('provider_note_comments_patient_id_foreign');
                $table->unsignedBigInteger('user_id')->index('provider_note_comments_user_id_foreign');
                $table->unsignedBigInteger('provider_note_id')->index('provider_note_comments_provider_note_id_foreign');
                $table->longText('body');
                $table->boolean('edited')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_note_comments');
    }
};