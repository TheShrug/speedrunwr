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
	        // `double precision`. Laravel 11+ reads float()'s second argument as
	        // a bit precision, not a digit count, so the old (12,3) asked for
	        // float(12) - which Postgres resolves to `real`, and a 12-hour run
	        // at millisecond resolution does not survive single precision.
	        $table->float('primaryTime')->nullable();
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
