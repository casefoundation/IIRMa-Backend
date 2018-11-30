<?php

use Vinelab\NeoEloquent\Schema\Blueprint;
use Vinelab\NeoEloquent\Migrations\Migration;

class CreateGeographicAreaLabel extends Migration
{
    protected $connection = 'neo4j';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @noinspection PhpUndefinedClassInspection */
        Neo4jSchema::label('GeographicArea', function (Blueprint $label) {
            $label->unique('countryCode');
            $label->unique('name');
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
            $label->dropUnique('countryCode');
            $label->dropUnique('name');
        });
    }
}
