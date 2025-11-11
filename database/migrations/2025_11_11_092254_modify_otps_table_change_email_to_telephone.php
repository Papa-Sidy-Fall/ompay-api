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
        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex(['email', 'utilise']);
            $table->dropColumn('email');
            $table->string('telephone')->after('id');
            $table->index(['telephone', 'utilise']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex(['telephone', 'utilise']);
            $table->dropColumn('telephone');
            $table->string('email')->after('id');
            $table->index(['email', 'utilise']);
        });
    }
};
