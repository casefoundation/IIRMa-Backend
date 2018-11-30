<?php

use Illuminate\Support\Facades\Schema;
use App\AnalyzeHandlers\InvalidData;
use Illuminate\Database\Migrations\Migration;

class MakeInvalidDataGeneric extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invalid_data', function ($table) {
            // Create new columns for table_name (1 column split into 2).
            $table->string('datasource');
            $table->string('datasource_id');
        });

        // Save Impactspace Data
        $results = InvalidData::where('impactspace_id', '<>', '')->get();
        foreach ($results as $result) {
            $result->datasource = "impactspace";
            $result->datasource_id = $result->impactspace_id;
            $result->save();
        }

        // Save Crunchbase Data
        $results = InvalidData::where('crunchbase_id', '<>', '')->get();
        foreach ($results as $result) {
            $result->datasource = "crunchbase";
            $result->datasource_id = $result->crunchbase_id;
            $result->save();
        }

        // Delete old columns
        Schema::table('invalid_data', function ($table) {
            $table->dropColumn('impactspace_id');
            $table->dropColumn('crunchbase_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invalid_data', function ($table) {
            // Create new columns for table_name (1 column split into 2).
            $table->string('impactspace_id');
            $table->string('crunchbase_id');
        });

        // Save Impactspace and Crunchbase Data
        $results = InvalidData::get();
        foreach ($results as $result) {
            if ($result->datasource == "impactspace") {
                $result->impactspace_id = $result->datasource_id;
            }
            if ($result->datasource == "crunchbase") {
                $result->crunchbase_id = $result->datasource_id;
            }
            $result->save();
        }

        // Delete old columns
        Schema::table('invalid_data', function ($table) {
            $table->dropColumn('datasource');
            $table->dropColumn('datasource_id');
        });
    }
}
