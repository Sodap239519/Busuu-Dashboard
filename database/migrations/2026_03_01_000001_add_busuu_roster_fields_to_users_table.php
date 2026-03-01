<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('team')->nullable()->after('avatar');
            $table->string('faculty')->nullable()->after('team');
            $table->string('major')->nullable()->after('faculty');
            $table->string('external_ref')->nullable()->after('major');
            $table->string('busuu_user_group')->nullable()->after('external_ref');
            $table->string('busuu_status')->nullable()->index()->after('busuu_user_group');
            $table->string('busuu_name_en')->nullable()->after('busuu_status');
            $table->string('busuu_name_th')->nullable()->after('busuu_name_en');
            $table->timestamp('last_imported_at')->nullable()->after('busuu_name_th');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['busuu_status']);
            $table->dropColumn([
                'team',
                'faculty',
                'major',
                'external_ref',
                'busuu_user_group',
                'busuu_status',
                'busuu_name_en',
                'busuu_name_th',
                'last_imported_at',
            ]);
        });
    }
};
