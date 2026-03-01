<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('duration_minutes')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->date('session_date');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('learning_sessions'); }
};
