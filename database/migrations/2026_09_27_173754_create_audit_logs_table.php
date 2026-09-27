<?php

declare(strict_types=1);

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
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            // The actor; null means the system (console, queue, or a guest such as a failed login).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // A snapshot of the actor, so the trail still says who acted after their account is deleted.
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('event', 50)->index();
            $table->nullableMorphs('auditable');
            $table->string('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            // Entries are never updated, so there is no updated_at.
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
