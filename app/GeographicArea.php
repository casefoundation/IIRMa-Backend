<?php
/**
 * Created by PhpStorm.
 * User: javie_000
 * Date: 14/12/2016
 * Time: 3:40 PM
 */

namespace App;

use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;

class GeographicArea extends NeoEloquent
{

    protected $primaryKey = 'id';

    protected $label = 'GeographicArea';

    /**
     * @var array Neo4j node fillable attributes
     */

    protected $fillable = ['name', 'type', 'country_code'];

    protected $connection = 'neo4j';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parentRegion()
    {
        return $this->hasOne(GeographicArea::class, 'BELONGS_TO');
    }

    public function regions()
    {
        return $this->belongsToMany(GeographicArea::class, 'BELONGS_TO');
    }
    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'BELONGS_TO');
    }
    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function investors()
    {
        return $this->belongsToMany(Investor::class, 'BELONGS_TO');
    }
    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function offices()
    {
        return $this->belongsToMany(Office::class, 'BELONGS_TO');
    }
}
