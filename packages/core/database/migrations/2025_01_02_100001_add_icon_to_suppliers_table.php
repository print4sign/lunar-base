<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'suppliers', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'suppliers', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
