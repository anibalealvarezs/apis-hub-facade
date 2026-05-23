<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('role')->default('viewer');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invitations');
    }
};
