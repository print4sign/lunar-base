<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'print_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_line_id')->nullable()->constrained($this->prefix.'order_lines')->nullOnDelete();
            $table->foreignId('cart_line_id')->nullable()->constrained($this->prefix.'cart_lines')->nullOnDelete();
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['order_line_id', 'status']);
            $table->index(['cart_line_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'print_assets');
    }
};
