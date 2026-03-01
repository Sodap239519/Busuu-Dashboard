<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language');
            $table->string('level');
            $table->text('description')->nullable();
            $table->integer('total_lessons')->default(0);
            $table->decimal('estimated_hours', 5, 1)->default(0);
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('courses'); }
};
