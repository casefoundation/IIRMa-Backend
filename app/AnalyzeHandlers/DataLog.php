<?php

namespace App\AnalyzeHandlers;

use Eloquent;

class DataLog extends Eloquent
{
    
    
    protected $table = 'data_log';
    
    protected $fillable = array(
        'datasource',
        'start_time',
        'end_time',
    );
}
