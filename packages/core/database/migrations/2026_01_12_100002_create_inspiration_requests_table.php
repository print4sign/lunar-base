<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'inspiration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained($this->prefix.'orders')
                ->cascadeOnDelete();
            $table->foreignId('order_line_id')
                ->nullable()
                ->constrained($this->prefix.'order_lines')
                ->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('token');
            $table->index(['order_id', 'order_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'inspiration_requests');
    }
};
