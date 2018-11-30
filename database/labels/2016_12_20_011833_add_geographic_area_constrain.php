<?php

use Vinelab\NeoEloquent\Schema\Blueprint;
use Vinelab\NeoEloquent\Migrations\Migration;

class AddGeographicAreaConstrain extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @noinspection PhpUndefinedClassInspection */
        Neo4jSchema::label('GeographicArea', function (Blueprint $label) {
            $label->dropUnique('name');
            $label->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /** @noinspection PhpUndefinedClassInspection */
        Neo4jSchema::label('GeographicArea', function (Blueprint $label) {
            $label->dropUnique('slug');
            $label->unique('name');
        });
    }
}
