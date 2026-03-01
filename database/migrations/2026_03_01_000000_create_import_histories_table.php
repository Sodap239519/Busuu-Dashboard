<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('import_id')->unique();
            $table->string('filename')->nullable();
            $table->string('type')->index(); // sessions/courses/monthly_report
            $table->unsignedInteger('rows')->default(0);
            $table->string('status')->index(); // queued|processing|success|error
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};