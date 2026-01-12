<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'supplier_products', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('configurator_schema');
            $table->timestamp('active_to')->nullable()->after('active');
            $table->string('replaced_by_external_id')->nullable()->after('active_to');
            $table->foreignId('replaced_by_id')
                ->nullable()
                ->after('replaced_by_external_id')
                ->constrained($this->prefix.'supplier_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'supplier_products', function (Blueprint $table) {
            $table->dropForeign(['replaced_by_id']);
            $table->dropColumn([
                'active',
                'active_to',
                'replaced_by_external_id',
                'replaced_by_id',
            ]);
        });
    }
};
