<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observation_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('original_filename');
            $table->string('archive_path')->nullable();
            $table->string('status', 32)->index();
            $table->boolean('auto_import')->default(true);
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('validated_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedBigInteger('total_observations')->default(0);
            $table->unsignedBigInteger('processed_observations')->default(0);
            $table->unsignedBigInteger('inserted_observations')->default(0);
            $table->unsignedBigInteger('duplicate_observations')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('observation_import_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('observation_import_id')
                ->constrained('observation_imports')
                ->cascadeOnDelete();
            $table->string('filename', 500);
            $table->string('stored_path');
            $table->unsignedInteger('position');
            $table->string('status', 32)->index();
            $table->unsignedInteger('observation_count')->default(0);
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['observation_import_id', 'position']);
            $table->index(['observation_import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observation_import_files');
        Schema::dropIfExists('observation_imports');
    }
};
