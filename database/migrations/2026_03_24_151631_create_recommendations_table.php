<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();

            // relations
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plat_id')->constrained()->cascadeOnDelete();

            // score
            $table->float('score')->default(0);
            
            // label
            $table->text('warning_message')->nullable();
            // statut (processing / ready)
            $table->enum('status', ['processing', 'ready'])->default('processing');

            $table->timestamps();

            // éviter doublons
            $table->unique(['user_id', 'plat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
