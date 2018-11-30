<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvalidDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invalid_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('entity_type');
            $table->string('entity_category');
            $table->string('title');
            $table->string('field');
            $table->string('value');
            $table->string('impactspace_id')->nullable();
            $table->string('crunchbase_id')->nullable();
            $table->string('update_timestamp');
                    
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
        //
        Schema::drop('invalid_data');
    }
}
