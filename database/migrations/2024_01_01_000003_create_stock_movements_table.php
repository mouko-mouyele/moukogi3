<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('type', ['entree', 'sortie']);
            $table->enum('motion_type', ['achat', 'retour', 'correction', 'vente', 'perte', 'casse', 'expiration'])->default('achat');
            $table->integer('quantity');
            $table->date('movement_date');
            $table->unsignedBigInteger('user_id');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('product_id');
            $table->index('movement_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
