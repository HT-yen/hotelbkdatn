<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStreetPlacesTable extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('street_places', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('place_id')->unsigned();
            $table->string('street_name');
            $table->timestamps();
            $table->foreign('place_id')->references('id')->on('places');
            $table->softDeletes();
            $table->unique(['place_id', 'street_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('street_places');
    }
}
