<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'inspirations', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('order_id')
                ->constrained($this->prefix.'orders')
                ->cascadeOnDelete();
            $table->foreignId('order_line_id')
                ->nullable()
                ->constrained($this->prefix.'order_lines')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Content type
            $table->string('type')->default('review'); // review, case_study

            // Common fields
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->json('text'); // Translatable review text
            $table->boolean('permission_granted')->default(false);

            // Extended fields (case_study only)
            $table->json('title')->nullable(); // Translatable title
            $table->string('company_name')->nullable();
            $table->string('project_type')->nullable();

            // Moderation
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            // Publishing
            $table->boolean('featured')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('rating');
            $table->index('featured');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'inspirations');
    }
};
