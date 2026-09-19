<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){Schema::create('cities',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug');$t->foreignId('province_id')->constrained()->cascadeOnDelete();$t->timestamps();$t->unique(['province_id','slug']);});} public function down(){Schema::dropIfExists('cities');} };
