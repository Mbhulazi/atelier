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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('collector')->after('email');
            $table->string('slug')->unique()->nullable()->after('role');
            $table->text('bio')->nullable()->after('slug');
            $table->string('avatar')->nullable()->after('bio');
            $table->string('stripe_account_id')->nullable();
            $table->string('stripe_mode')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'slug', 'bio', 'avatar', 'stripe_account_id', 'stripe_mode']);
        });
    }
};
