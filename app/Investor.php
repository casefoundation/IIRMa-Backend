<?php namespace App;

use Vinelab\NeoEloquent\Eloquent\SoftDeletes;
use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;

/**
 * Class Investor
 * @package App
 */
class Investor extends NeoEloquent
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    /**
     * @var string Set custom search key for ::find method instead of internal Neo4j int IDs
     */
    protected $primaryKey = 'id';

    /**
     * @var string Neo4j node label
     */
    protected $label = 'Investor';

    /**
     * @var array Neo4j node fillable attributes
     */
    protected $fillable = [
        'name',
        'impactspace_id',
        'impactspace_updated_at',
        'crunchbase_id',
        'crunchbase_updated_at',
        'crunchbase_url',
        'crunchbase_fields',
        'crunchbase_fields_count',
        'slug',
        'overview',
        'mission_statement',
        'legal_structure',
        'website',
        'network_map_ready',
        'email',
        'facebook',
        'twitter',
        'linkedin',
        'company_logo',
        'phone',
        'founded_date',
        'number_of_employees',
        'external_links',
        'type'
    ];

    protected $connection = 'neo4j';

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany The companies for the current investor
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'FUNDED');
    }

    public function fundings()
    {
        return $this->companies()->edges();
    }

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function impactObjectives()
    {
        return $this->belongsToMany(ImpactObjective::class, 'HAS_INVESTOR');
    }

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\HasMany
     */
    public function offices()
    {
        return $this->hasMany(Office::class, 'LOCATED_AT');
    }
    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function geographicAreas()
    {
        return $this->belongsToMany(GeographicArea::class, 'HAS_INVESTOR');
    }
}
