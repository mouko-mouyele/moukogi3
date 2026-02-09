<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->date('inventory_date');
            $table->unsignedBigInteger('user_id');
            $table->text('notes')->nullable();
            $table->enum('status', ['en_cours', 'termine', 'archive'])->default('en_cours');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('inventory_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
