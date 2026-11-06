<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A "pop" is one sharing circle (trip, flat, dinner...). Reached by its secret slug link.
        Schema::create('pops', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 16)->unique();
            $t->string('name', 60);
            $t->string('emoji', 8)->default('🍕');
            $t->string('currency', 4)->default('₹');
            $t->timestamps();
        });

        Schema::create('members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pop_id')->constrained('pops')->cascadeOnDelete();
            $t->string('name', 40);
            $t->timestamps();
        });

        // Money is stored as integer minor units (paise / cents) so nothing drifts.
        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pop_id')->constrained('pops')->cascadeOnDelete();
            $t->foreignId('paid_by')->constrained('members')->cascadeOnDelete();
            $t->string('title', 80);
            $t->unsignedBigInteger('amount_cents');
            $t->date('spent_on');
            $t->timestamps();
        });

        Schema::create('expense_shares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $t->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $t->unsignedBigInteger('share_cents');
            $t->timestamps();
        });

        // "X paid Y back" records
        Schema::create('settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pop_id')->constrained('pops')->cascadeOnDelete();
            $t->foreignId('from_member_id')->constrained('members')->cascadeOnDelete();
            $t->foreignId('to_member_id')->constrained('members')->cascadeOnDelete();
            $t->unsignedBigInteger('amount_cents');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('expense_shares');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('members');
        Schema::dropIfExists('pops');
    }
};
