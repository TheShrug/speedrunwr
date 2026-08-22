<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLikedRunsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('liked_runs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('runId', 10);
	        $table->string('gameId')->nullable();
	        $table->string('categoryId')->nullable();
	        $table->string('levelId')->nullable();
	        $table->string('userId')->nullable();
	        $table->string('platformId')->nullable();
	        $table->string('regionId')->nullable();
	        $table->integer('competition')->default(null)->nullable();
	        // `double precision`, matching records.primaryTime. See the note in
	        // 2018_04_26_013655_create_records_table: float()'s old (12,3)
	        // asked Postgres for `real` and lost milliseconds on long runs.
	        $table->float('primaryTime')->nullable();
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
        Schema::dropIfExists('liked_runs');
    }
}
