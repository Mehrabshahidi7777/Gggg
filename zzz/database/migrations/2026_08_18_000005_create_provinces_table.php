<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){Schema::create('provinces',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->timestamps();});} public function down(){Schema::dropIfExists('provinces');} };
