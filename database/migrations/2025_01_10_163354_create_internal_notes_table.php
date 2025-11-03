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
        if (!Schema::hasTable('internal_notes')) {
            Schema::create('internal_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('patient_id')->nullable()->index('internal_notes_patient_id_foreign');
                $table->unsignedBigInteger('user_id')->nullable()->index('internal_notes_user_id_foreign');
                $table->longText('body');
                $table->boolean('edited')->default(false);
                $table->timestamps();
                $table->unsignedBigInteger('comment_id')->nullable()->index('internal_notes_comment_id_foreign');
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_notes');
    }
};