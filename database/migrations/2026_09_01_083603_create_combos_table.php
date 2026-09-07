<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('description')->nullable();
            $table->integer('min_guests')->default(10);
            $table->decimal('price_per_guest', 8, 2);
            $table->text('image_url')->nullable();
            $table->text('badge')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('menu_item_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('combos'); }
};