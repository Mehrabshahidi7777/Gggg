<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){Schema::create('categories',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();$t->enum('type',['product','service']);$t->string('icon')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});} public function down(){Schema::dropIfExists('categories');} };
