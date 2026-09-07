<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->dateTime('event_date');
            $table->string('address');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('total_estimate', 10, 2)->nullable();
            $table->string('order_type')->nullable();
            $table->json('order_details')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bookings'); }
};