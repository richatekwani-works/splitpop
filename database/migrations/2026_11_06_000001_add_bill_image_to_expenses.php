<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Vercel has no permanent disk, so bill photos live in the database (base64 text) instead of storage/.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $t) {
            $t->longText('bill_data')->nullable();
            $t->string('bill_mime', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $t) {
            $t->dropColumn(['bill_data', 'bill_mime']);
        });
    }
};
