<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->enum('type', ['stock_minimum', 'rupture_imminente', 'expiration_proche', 'surstock']);
            $table->string('title');
            $table->text('message');
            $table->enum('status', ['active', 'resolved', 'dismissed'])->default('active');
            $table->boolean('email_sent')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
