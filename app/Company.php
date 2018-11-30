<?php namespace App;

use Everyman\Neo4j\Exception;
use Vinelab\NeoEloquent\Eloquent\SoftDeletes;
use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;

/**
 * Class Company
 *
 * @package App
 */
class Company extends NeoEloquent
{

    use SoftDeletes;

    /**
     * @var array Valid legal structure values
     */
    public static $allowedLegalStructures = [
        'benefit corporation',
        'c corporation',
        'cooperative',
        'hybrid',
        'limited liability company',
        'nonprofit',
        'partnership',
        's corporation',
        'sole-proprietorship',
        'holding company',
        'government',
        'other',
    ];
    /**
     * @var array Valid industry values
     */
    public static $allowedIndustries = [
        'accommodation & food service',
        'administrative & support services',
        'agriculture, forestry & fishing',
        'arts, entertainment & recreation',
        'construction',
        'education',
        'energy',
        'financial & insurance activities',
        'human health & social work',
        'information, communication & technology',
        'manufactured goods',
        'mining & quarrying',
        'other services',
        'professional & technical services',
        'publishing - print',
        'real estate, design & building',
        'rental & repair',
        'retail',
        'transportation & storage',
        'waste management & recycling',
        'water & sewerage',
        'wholesale',
    ];

    protected $dates = ['deleted_at'];

    /**
     * @var string Set custom search key for ::find method instead of internal Neo4j int IDs
     */
    protected $primaryKey = 'id';

    /**
     * @var string Neo4j node label
     */
    protected $label = 'Company';
    
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
        'network_map_ready',
        'mission_statement',
        'industry',
        'certifications',
        'legal_structure',
        'website',
        'email',
        'facebook',
        'twitter',
        'linkedin',
        'company_logo',
        'phone',
        'founded_date',
        'number_of_employees',
        'external_links',
    ];

    protected $connection = 'neo4j';

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany The investors for the current company
     */
    public function investors()
    {
        return $this->belongsToMany(Investor::class, 'FUNDED_BY');
    }

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Edges\Edge
     */
    public function funds()
    {
        return $this->investors()->edges();
    }

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function impactObjectives()
    {
        return $this->belongsToMany(ImpactObjective::class, 'HAS_COMPANY');
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
        return $this->belongsToMany(GeographicArea::class, 'HAS_COMPANY');
    }

    /**
     * Mutator for Legal Structure Attribute
     *
     * Validates that the legal structure is any of the values in \App\Company::$allowedLegalStructures array
     *
     * @uses \App\Company::$allowedLegalStructures
     * @param string $value any valid value for Legal structure
     * @throws Exception if the legal structure is a non-valid value
     */
    public function setLegalStructureAttribute($value)
    {
        if (!empty($value) && !in_array(strtolower($value), self::$allowedLegalStructures)) {
            throw new Exception('Invalid value for legal_structure field in Company.');
        }
        $this->attributes['legal_structure'] = empty($value)? null : strtolower($value);
    }

    /**
     * Mutator for Industry Attribute
     *
     * Validates that the industry is any of the values in \App\Company::$allowedIndustries array
     *
     * @uses \App\Company::$allowedIndustries
     * @param $value
     * @throws Exception if the industry is a non-valid value
     */
    public function setIndustryAttribute($value)
    {
        if (!empty($value) && !in_array(strtolower($value), self::$allowedIndustries)) {
            throw new Exception('Invalid value for industry field in Company.');
        }
        $this->attributes['industry'] = empty($value)? null : strtolower($value);
    }
}
