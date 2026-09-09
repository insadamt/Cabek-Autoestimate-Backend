<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('observation_import_files');
        Schema::dropIfExists('observation_imports');
    }

    public function down(): void
    {
        // The previous queued GUI import workflow has been retired permanently.
    }
};
