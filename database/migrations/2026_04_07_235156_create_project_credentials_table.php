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
        Schema::create('project_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // facebook, google, amazon, etc.
            $table->text('token')->nullable(); // Encrypted at Model level
            $table->text('refresh_token')->nullable(); // Encrypted at Model level
            $table->string('external_user_id')->nullable();
            $table->json('scopes')->nullable(); 
            $table->json('meta')->nullable(); // Extra provider-specific data
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Unicidad por proyecto y proveedor: No podemos tener 2 credenciales de FB para el mismo proyecto (por ahora)
            $table->unique(['project_id', 'provider']);
        });

        // Intentar migrar datos existentes si las columnas existen en projects
        if (Schema::hasColumn('projects', 'facebook_user_token')) {
            $projects = \Illuminate\Support\Facades\DB::table('projects')->get();
            foreach ($projects as $project) {
                // Migrar Facebook
                if (!empty($project->facebook_user_token)) {
                    \Illuminate\Support\Facades\DB::table('project_credentials')->insert([
                        'project_id' => $project->id,
                        'provider' => 'facebook',
                        'token' => $project->facebook_user_token,
                        'external_user_id' => $project->facebook_user_id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                // Migrar Google
                if (!empty($project->google_refresh_token)) {
                    \Illuminate\Support\Facades\DB::table('project_credentials')->insert([
                        'project_id' => $project->id,
                        'provider' => 'google',
                        'refresh_token' => $project->google_refresh_token,
                        'external_user_id' => $project->google_user_id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_credentials');
    }
};
