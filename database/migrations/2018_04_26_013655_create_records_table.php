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
            $table->string('runId')->index();
            $table->string('gameId')->nullable()->index();
            $table->string('categoryId')->nullable()->index();
	        $table->string('levelId')->nullable()->index();
	        $table->string('userId')->nullable()->index();
	        $table->string('platformId')->nullable()->index();
	        $table->string('regionId')->nullable()->index();
	        $table->integer('competition')->default(0);
	        $table->float('primaryTime',12,3)->nullable();
	        $table->dateTime('date')->nullable();
	        $table->string('youtubeId')->nullable()->index();
	        $table->string('twitchId')->nullable()->index();
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
