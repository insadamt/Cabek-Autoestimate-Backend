<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('part_price_observations', 'details')) {
            return;
        }

        Schema::table('part_price_observations', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('part_price_observations', 'details')) {
            return;
        }

        Schema::table('part_price_observations', function (Blueprint $table) {
            $table->text('details')->nullable()->after('source_url');
        });
    }
};
