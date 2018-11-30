<?php

use Vinelab\NeoEloquent\Schema\Blueprint;
use Vinelab\NeoEloquent\Migrations\Migration;

class IndexCompanyInvestor extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Neo4jSchema::label('Company', function (Blueprint $label) {
            $label->index('name');
            $label->index('website');
        });
        Neo4jSchema::label('Investor', function (Blueprint $label) {
            $label->index('name');
            $label->index('website');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Neo4jSchema::label('Company', function (Blueprint $label) {
            $label->dropIndex('name');
            $label->dropIndex('website');
        });
        Neo4jSchema::label('Investor', function (Blueprint $label) {
            $label->dropIndex('name');
            $label->dropIndex('website');
        });
    }
}
