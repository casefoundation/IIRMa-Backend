<?php

use Vinelab\NeoEloquent\Schema\Blueprint;
use Vinelab\NeoEloquent\Migrations\Migration;

class CreateImpactObjectiveLabel extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @noinspection PhpUndefinedClassInspection */
        Neo4jSchema::label('ImpactObjective', function (Blueprint $label) {
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
        Neo4jSchema::label('ImpactObjective', function (Blueprint $label) {
            $label->dropUnique('slug');
        });
    }
}
