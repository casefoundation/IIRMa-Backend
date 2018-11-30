<?php

namespace App\AnalyzeHandlers;

use Eloquent;

class InvalidData extends Eloquent
{
    
    
    protected $table = 'invalid_data';
    
    protected $fillable = array(
        'entity_type',      // Entity type eg: 'company'
        'entity_category',  // entity category eg: 'social_objectives'
        'title',            // entity title, just for illustration
        'field',            // field name, eg: 'twitter'
        'value',            // field value, could be blank or malformed data
        'datasource',       // datasource
        'datasource_id',    // datasource id if available
        'update_timestamp'  // datasource update timestamp to track malformed updates
    );
}
