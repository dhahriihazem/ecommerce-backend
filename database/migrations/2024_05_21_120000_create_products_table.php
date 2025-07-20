<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Shared fields
            $table->string('name');
            $table->text('description');
            $table->enum('type', ['fixed_price', 'auction']);

            // Fields for 'fixed_price' products
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();

            // Fields for 'auction' products
            $table->decimal('starting_price', 10, 2)->nullable();
            $table->decimal('current_highest_bid', 10, 2)->nullable();
            $table->timestamp('auction_end_time')->nullable();

            $table->timestamps();
        });
    }

  
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};