<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('event_name');
            $table->timestamp('event_occur')->nullable();
            $table->boolean('is_received')->default(false);
            $table->string('author');
            $table->timestamps();
            
            // Optional index for frequent queries
            $table->index('event_id');
            $table->index('event_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}