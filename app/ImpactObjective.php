<?php
/**
 * Created by PhpStorm.
 * User: javie_000
 * Date: 12/12/2016
 * Time: 10:36 PM
 */

namespace App;

use Vinelab\NeoEloquent\Eloquent\SoftDeletes;
use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;

class ImpactObjective extends NeoEloquent
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $label = 'ImpactObjective';
    /**
     * @var array Neo4j node fillable attributes
     */

    protected $fillable = ['name', 'type'];

    protected $primaryKey = 'id';
    protected $connection = 'neo4j';

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'HAS_OBJECTIVE');
    }

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function investors()
    {
        return $this->belongsToMany(Investor::class, 'HAS_OBJECTIVE');
    }
}
