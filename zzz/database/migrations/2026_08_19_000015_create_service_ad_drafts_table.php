<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('service_ad_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->json('image_paths')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('service_ad_drafts'); }
};
