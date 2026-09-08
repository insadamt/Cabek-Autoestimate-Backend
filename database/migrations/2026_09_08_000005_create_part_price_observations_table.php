<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_price_observations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('part_id');
            $table->unsignedBigInteger('source_id');

            $table->unsignedSmallInteger('production_from')->nullable();
            $table->unsignedSmallInteger('production_to')->nullable();

            $table->string('type', 20);
            $table->decimal('price', 12, 2);

            $table->string('source_reference', 255)->nullable();
            $table->text('source_url')->nullable();
            $table->text('details')->nullable();

            $table->dateTime('observed_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('model_id', 'observations_model_fk')
                ->references('id')
                ->on('models');

            $table->foreign('part_id', 'observations_part_fk')
                ->references('id')
                ->on('parts');

            $table->foreign('source_id', 'observations_source_fk')
                ->references('id')
                ->on('sources');

            $table->index(['model_id', 'part_id'], 'observations_model_part');
            $table->index(['model_id', 'part_id', 'observed_at'], 'observations_model_part_date');
            $table->index('source_id', 'observations_source');
            $table->index('observed_at', 'observations_observed_at');
            $table->index(
                ['model_id', 'part_id', 'production_from', 'production_to'],
                'observations_model_part_years'
            );
        });

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement(
                "ALTER TABLE part_price_observations
                 ADD CONSTRAINT observations_type_check
                 CHECK (type IN ('original', 'adaptable', 'occasion'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('part_price_observations');
    }
};
