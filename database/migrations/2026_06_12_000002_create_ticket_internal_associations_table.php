<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_internal_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['support_ticket_id', 'user_id']);
        });

        Schema::create('ticket_internal_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['support_ticket_id', 'project_id']);
        });

        Schema::create('ticket_internal_billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['support_ticket_id', 'billing_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_internal_billing_profiles');
        Schema::dropIfExists('ticket_internal_projects');
        Schema::dropIfExists('ticket_internal_users');
    }
};
