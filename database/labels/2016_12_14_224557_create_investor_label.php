<?php

use Vinelab\NeoEloquent\Schema\Blueprint;
use Vinelab\NeoEloquent\Migrations\Migration;

class CreateInvestorLabel extends Migration
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
        Neo4jSchema::label('Investor', function (Blueprint $label) {
            $label->unique('slug');
            $label->unique('impactspaceId');
            $label->unique('crunchbaseId');
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
         Neo4jSchema::label('Investor', function (Blueprint $label) {
             $label->dropUnique('slug');
             $label->dropUnique('impactspaceId');
             $label->dropUnique('crunchbaseId');
         });
    }
}
