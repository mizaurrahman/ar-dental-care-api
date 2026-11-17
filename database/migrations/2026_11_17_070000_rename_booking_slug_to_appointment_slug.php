<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'booking_slug') && !Schema::hasColumn('users', 'appointment_slug')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('booking_slug', 'appointment_slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'appointment_slug') && !Schema::hasColumn('users', 'booking_slug')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('appointment_slug', 'booking_slug');
            });
        }
    }
};
