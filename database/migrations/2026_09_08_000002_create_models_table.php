<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marque_id');
            $table->string('libelle', 150);
            $table->timestamps();

            $table->foreign('marque_id', 'models_marque_fk')
                ->references('id')
                ->on('marques');

            $table->unique(['marque_id', 'libelle'], 'models_marque_libelle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('models');
    }
};
