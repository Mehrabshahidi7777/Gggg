<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){Schema::create('contact_messages',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->nullable();$t->string('phone')->nullable();$t->string('subject')->nullable();$t->text('message');$t->boolean('is_read')->default(false);$t->timestamps();});} public function down(){Schema::dropIfExists('contact_messages');} };
