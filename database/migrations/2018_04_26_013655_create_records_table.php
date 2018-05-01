<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('runId');
            $table->string('gameId');
            $table->string('categoryId');
            $table->string('userID');
            $table->boolean('hasTwitch');
            $table->boolean('hasYoutube');
            $table->integer('competition');
            $table->float('primaryTime');
            $table->date('date');
            $table->string('youtubeId')->nullable();
            $table->string('twitchId')->nullable();
            $table->string('level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('records');
    }
}
