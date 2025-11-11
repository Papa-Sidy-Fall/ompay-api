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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('code', 4);
            $table->enum('type', ['inscription', 'connexion', 'transaction']);
            $table->timestamp('expire_at');
            $table->boolean('utilise')->default(false);
            $table->timestamps();

            $table->index(['email', 'utilise']);
            $table->index('expire_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
