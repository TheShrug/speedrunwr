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
            $table->string('gameId')->nullable();
            $table->string('categoryId')->nullable();
	        $table->string('levelId')->nullable();
	        $table->string('userId')->nullable();
	        $table->string('platformId')->nullable();
	        $table->string('regionId')->nullable();
	        $table->integer('competition')->default(0);
	        $table->float('primaryTime',12,3)->nullable();
	        $table->dateTime('date')->nullable();
	        $table->string('youtubeId')->nullable();
	        $table->string('twitchId')->nullable();
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
