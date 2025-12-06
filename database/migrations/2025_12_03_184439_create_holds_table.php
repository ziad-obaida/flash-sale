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
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('qty')->unsigned();
            $table->enum('status', ['active','expired','consumed','cancelled','completed'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('consumed_by_order_id')->nullable(); // بدون FK مؤقتًا لتجنب مشاكل الترتيب
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holds');
    }
};
