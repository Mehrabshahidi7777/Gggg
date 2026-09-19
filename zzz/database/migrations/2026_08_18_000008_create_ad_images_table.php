<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){Schema::create('ad_images',function(Blueprint $t){$t->id();$t->foreignId('ad_id')->constrained()->cascadeOnDelete();$t->string('path');$t->boolean('is_primary')->default(false);$t->timestamps();});} public function down(){Schema::dropIfExists('ad_images');} };
