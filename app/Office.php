<?php
/**
 * Created by PhpStorm.
 * User: javie_000
 * Date: 14/12/2016
 * Time: 3:54 PM
 */

namespace App;

use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;
use Everyman\Neo4j\Cypher\Query;

class Office extends NeoEloquent
{
    protected $label = 'Office';

    /**
     * @var array Neo4j node fillable attributes
     */

    protected $fillable = [
        'crunchbase_id',
        'address',
        'city',
        'state',
        'headquarter',
        'postal_code',
        'country_code'];

    protected $connection = 'neo4j';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'LOCATED_AT');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'LOCATED_AT');
    }

    /**
     * @return \Vinelab\NeoEloquent\Eloquent\Relations\BelongsToMany
     */
    public function geographicAreas()
    {
        return $this->belongsToMany(GeographicArea::class, 'HAS_OFFICE');
    }

    /**
     * @param $params
     * @return object
     */
    public static function companyLocatedAtOffice($params)
    {
        $queryString = '
            MATCH (o:Office), (i:Company), (g:GeographicArea)
            WHERE ID(o)={office_id} AND i.impactspace_id={company_id} AND ID(g)={geographic_id}
            CREATE UNIQUE (i)-[:LOCATED_AT]->(o)-[:BELONGS_TO]->(g)
        ';
        return self::executeQuery($queryString, $params);
    }

    /**
     * @param $params
     * @return object
     */
    public static function investorLocatedAtOffice($params)
    {
        $queryString = '
            MATCH (o:Office), (i:Investor), (g:GeographicArea)
            WHERE ID(o)={office_id} AND i.impactspace_id={investor_id} AND ID(g)={geographic_id}
            CREATE UNIQUE (i)-[:LOCATED_AT]->(o)-[:BELONGS_TO]->(g)
        ';
        return self::executeQuery($queryString, $params);
    }

    /**
     * Return result of query execution
     *
     * @param string $query_str
     * @param array  $params
     * @return object
     */
    public static function executeQuery($query_str, $params)
    {
        $client = \DB::connection('neo4j')->getClient();
        $query = new Query($client, $query_str, $params);
        return $query->getResultSet();
    }
}
