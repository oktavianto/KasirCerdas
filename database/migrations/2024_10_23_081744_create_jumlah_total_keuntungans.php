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
        Schema::create('jumlah_total_keuntungans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bisnis_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cabangs_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('total_keuntungan', 10,2);        
            $table->timestamps();
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jumlah_total_keuntungan');
    }
};
