<?php

namespace App\Api\V1\Controllers;

use Everyman\Neo4j\Cypher\Query;
use App\Http\Controllers\Controller;

class GeographicAreaController extends Controller
{
    protected $respose;

    /**
     * Return json for "/geographic/top_funded/{top_number}" route
     * @param $top_number
     * @return array
     */
    public function topFunded($top_number)
    {
        $investors_ammounts = [];
        if (intval($top_number)) {
            $queryString = 'Match (:Investor)-[fund:FUNDED]->(company:Company)-[:BELONGS_TO*2]->(geo:GeographicArea) 
            RETURN SUM(toInt(fund.amount)) AS total, geo.name, id(geo), collect(company.industry) as industries 
            ORDER BY total DESC LIMIT {top_number}';
            $investors_raw = $this->executeQuery($queryString, ["top_number"=>intval($top_number)]);
            foreach ($investors_raw as $row) {
                $total_amount = $row[0];
                $geo_name = $row[1];
                $geo_id = $row[2];
                $industries = $row[3];
                $industries_arr = [];
                foreach ($industries as $industry) {
                    if (isset($industries_arr["$industry"])) {
                        $industries_arr["$industry"]++;
                    } else {
                        $industries_arr["$industry"]=1;
                    }
                }
                $investors_ammounts[$geo_id] = [
                    "name" => $geo_name,
                    "amount" => $total_amount,
                    "industries" => $industries_arr
                ];
            }
        }

        return $investors_ammounts;
    }

    /**
     * Return data is valid value
     *
     * @param $obj
     * @param $field
     * @return string
     */
    public function returnValidValue($obj, $field)
    {
        return isset($obj[$field])?$obj[$field]:"";
    }

    /**
     * Return result of query execution
     *
     * @param string $query_str
     * @param array  $params
     * @return object
     */
    public function executeQuery($query_str, $params)
    {
        $client = \DB::connection('neo4j')->getClient();
        $query = new Query($client, $query_str, $params);
        return $query->getResultSet();
    }
}
