<?php

namespace App\Api\V1\Controllers;

use Everyman\Neo4j\Cypher\Query;
use App\Http\Controllers\Controller;
use Dingo\Api\Contract\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalController extends Controller
{

    /**
     * Return json for "/global/total_funds" route call
     *
     * @return array
     */
    public function totalFunds()
    {
        $result = array();

        $queryString = 'MATCH (:Investor)-[fund:FUNDED]->(:Company) 
                        RETURN  count(fund) as total, sum(toInt(fund.amount)) as amount';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_funds"] = $row[0];
            $result["total_funds_amount"] = $row[1];
        }

        $queryString = 'MATCH (:Investor)-[fund:FUNDED]->(:Company) 
                        WHERE toInt(fund.amount) > 0 
                        RETURN  count(fund) as total';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_funds_without_0"] = $row[0];
        }

        $queryString = 'MATCH (c:Company)<-[f:FUNDED]-(i:Investor) 
                        RETURN distinct(f.round), count(f) as count 
                        ORDER BY count desc';
        $investors_raw = $this->executeQuery($queryString, []);
        $investment_types = [];
        foreach ($investors_raw as $row) {
            $type_i = $row[0]==null?"unknown":$row[0];
            $investment_types[] = ["type"=>$type_i, "count"=>$row[1]];
        }
        $result["funds_types"] = $investment_types;

        $queryString = 'MATCH (c:Company)<-[f:FUNDED]-(i:Investor) 
                        RETURN distinct(f.round), SUM(toInt(f.amount)) as count 
                        ORDER BY count desc';
        $investors_raw = $this->executeQuery($queryString, []);
        $investment_types = [];
        foreach ($investors_raw as $row) {
            $type_i = $row[0]==null?"unknown":$row[0];
            $investment_types[] = ["type"=>$type_i, "count"=>$row[1]];
        }
        $result["funds_types_by_amount"] = $investment_types;

        return $result;
    }

    /**
     * Return json for "/global/total_companies_with_funds" route call
     *
     * @return array
     */
    public function totalCompaniesWithFunds()
    {
        $result = array();

        $queryString = 'MATCH (:Investor)-[:FUNDED]->(c:Company) 
                        RETURN  count(distinct(c)) as total';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_companies_with_funds"] = $row[0];
        }

        return $result;
    }

    /**
     * Return json for "/global/global_data" route call
     *
     * @return array
     */
    public function globalData()
    {
        $result = array();

        $queryString = 'MATCH (n:Company) RETURN  count(n) as total';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_companies"] = $row[0];
        }

        $queryString = 'MATCH (:Company)-[n:FUNDED]-(:Investor) 
                        RETURN  sum(toInt(n.amount)) as total';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_investment"] = $row[0];
        }

        $queryString = 'MATCH (n:Investor) RETURN  count(n) as total';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_investors"] = $row[0];
        }

        return $result;
    }

    /**
     * Return json for "/global/global_date_data" route call
     *
     * @return array
     */
    public function globalDateData()
    {
        $result = array();

        $impactspace = DB::table('data_log')
                            ->select("start_time", "end_time")
                            ->where('datasource', 'impactspace')
                            ->orderBy('id', 'desc')
                            ->first();
        $result["impactspace"] = $impactspace;

        $crunchbase = DB::table('data_log')
                            ->select("start_time", "end_time")
                            ->where('datasource', 'crunchbase')
                            ->orderBy('id', 'desc')
                            ->first();
        $result["crunchbase"] = $crunchbase;

        return $result;
    }

    /**
     * Return json for "/global/impact_objectives" route call
     *
     * @return array
     */
    public function impactObjectives()
    {
        // grab credentials from the request
        $result = array();

        $queryString = 'MATCH (n:ImpactObjective) RETURN count(n)';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_impact_objectives"] = $row[0];
        }

        $queryString = 'MATCH (n:ImpactObjective)<--(c:Company) RETURN count(distinct(c)) as count';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_companies_with_impact_objectives"] = $row[0];
        }

        $queryString = 'MATCH (n:ImpactObjective)<--(c:Investor) RETURN count(distinct(c)) as count';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            $result["total_investors_with_impact_objectives"] = $row[0];
        }

        $this->updateResultIO($result);


        return $result;
    }

    /**
     * Add values to result array
     * @param $result
     */
    private function updateResultIO(&$result)
    {
        $queryString = 'MATCH (c:Company) 
                        OPTIONAL MATCH (c)-[o:HAS_OBJECTIVE]->(:ImpactObjective) 
                        RETURN id(c), count(o)';
        $investors_raw = $this->executeQuery($queryString, []);
        $companies_count = [];
        foreach ($investors_raw as $row) {
            if (!isset($companies_count[$row[1]])) {
                $companies_count[$row[1]]=0;
            }
            $companies_count[$row[1]]++;
        }
        $result["companies_count_by_io"]=$companies_count;

        $queryString = 'MATCH (c:Investor) 
                        OPTIONAL MATCH (c)-[o:HAS_OBJECTIVE]->(:ImpactObjective) 
                        RETURN id(c), count(o)';
        $investors_raw = $this->executeQuery($queryString, []);
        $investors_count = [];
        foreach ($investors_raw as $row) {
            if (!isset($investors_count[$row[1]])) {
                $investors_count[$row[1]]=0;
            }
            $investors_count[$row[1]]++;
        }
        $result["investors_count_by_io"]=$investors_count;

        $queryString = 'MATCH (n:ImpactObjective)<--(c:Company) 
                        RETURN n.name, n.type, count(distinct(c)) as count 
                        ORDER BY count DESC';
        $investors_raw = $this->executeQuery($queryString, []);
        $io_companies = [];
        foreach ($investors_raw as $row) {
            $io_companies[]=["type"=>$row[1],"name"=>$row[0],"count"=>$row[2],];
        }
        $result["io_with_companies_count"]=$io_companies;

        $queryString = 'MATCH (n:ImpactObjective)<--(c:Investor) 
                        RETURN n.name, n.type, count(distinct(c)) as count 
                        ORDER BY count DESC';
        $investors_raw = $this->executeQuery($queryString, []);
        $io_investors = [];
        foreach ($investors_raw as $row) {
            $io_investors[]=["type"=>$row[1],"name"=>$row[0],"count"=>$row[2],];
        }
        $result["io_with_investors_count"]=$io_investors;
    }

    /**
     * Return json for "/global/geographic_areas" route call
     *
     * @return array
     */
    public function geographicAreas(Request $request)
    {
        $filters = $request->only('show_continent');
        $show_continent = $filters['show_continent'];

        $result = array();

        $this->setGeographicAreasWithCompanies($result, $show_continent);
        $this->setGeographicAreasWithInvestors($result, $show_continent);

        return $result;
    }

    private function setGeographicAreasWithCompanies(&$result, $show_continent)
    {
        $geo_companies = [];
        $queryString = 'MATCH (c:Company) 
                        OPTIONAL MATCH (c)-[:LOCATED_AT]->(:Office)-[o:BELONGS_TO]->(g:GeographicArea) 
                        RETURN  g.name, g.type, count(distinct(c)) as count 
                        ORDER BY count desc';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            if ($row[0] == null) {
                if ($show_continent) {
                    $geo_companies["undisclosed"]=["name"=>"Undisclosed","count"=>$row[2]];
                } else {
                    $geo_companies[]=["name"=>"Undisclosed","count"=>$row[2]];
                }
            }
        }
        $queryString = 'MATCH (g:GeographicArea)-->(gc:GeographicArea {type:"region"}) 
                        WHERE gc.name <> "Global" 
                        OPTIONAL MATCH (c:Company)-[:LOCATED_AT]->(:Office)-[o:BELONGS_TO]->(g) 
                        RETURN gc.name, gc.slug, g.name, g.type, count(distinct(c)) as count 
                        ORDER BY count desc';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            if ($show_continent) {
                if (!isset($geo_companies[$row[1]])) {
                    $geo_companies[$row[1]]=["name"=>$row[0],"type"=>"region","count"=>0];
                }
                $geo_companies[$row[1]]["count"]+=$row[4];
                $geo_companies[$row[1]]["countries"][]=["name"=>$row[2],"type"=>$row[3],"count"=>$row[4],];
            } else {
                $geo_companies[]=["name"=>$row[2],"type"=>$row[3],"count"=>$row[4],];
            }
        }

        $result["geographic_areas_with_companies"]=$this->arraySort($geo_companies, "count", SORT_DESC);
    }

    private function setGeographicAreasWithInvestors(&$result, $show_continent)
    {
        $queryString = 'MATCH (c:Investor) 
                        OPTIONAL MATCH (c)-[:LOCATED_AT]->(:Office)-[o:BELONGS_TO]->(g:GeographicArea) 
                        RETURN  g.name, g.type, count(distinct(c)) as count 
                        ORDER BY count desc';
        $investors_raw = $this->executeQuery($queryString, []);
        $geo_investors = [];
        foreach ($investors_raw as $row) {
            if ($row[0] == null) {
                if ($show_continent) {
                    $geo_investors["undisclosed"]=["name"=>"Undisclosed","count"=>$row[2]];
                } else {
                    $geo_investors[]=["name"=>"Undisclosed","count"=>$row[2]];
                }
            }
        }
        $queryString = 'MATCH (g:GeographicArea)-->(gc:GeographicArea {type:"region"}) 
                        WHERE gc.name <> "Global" 
                        OPTIONAL MATCH (c:Investor)-[:LOCATED_AT]->(:Office)-[o:BELONGS_TO]->(g) 
                        RETURN gc.name, gc.slug, g.name, g.type, count(distinct(c)) as count 
                        ORDER BY count desc';
        $investors_raw = $this->executeQuery($queryString, []);
        foreach ($investors_raw as $row) {
            if ($show_continent) {
                if (!isset($geo_investors[$row[1]])) {
                    $geo_investors[$row[1]]=["name"=>$row[0],"type"=>"region","count"=>0];
                }
                $geo_investors[$row[1]]["count"]+=$row[4];
                $geo_investors[$row[1]]["countries"][] = ["name" => $row[2], "type" => $row[3], "count" => $row[4],];
            } else {
                $geo_investors[] = ["name" => $row[2], "type" => $row[3], "count" => $row[4],];
            }
        }
        $result["geographic_areas_with_investors"]=$this->arraySort($geo_investors, "count", SORT_DESC);
    }
    /**
     * Sort Array
     *
     * @param $array
     * @param $on
     * @param int $order
     * @return array
     */
    private function arraySort($array, $on, $order = SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }


    /**
     * Return all data for "/global/all_data" route
     * @param Request $request
     * @return array
     */
    public function allData(Request $request)
    {
        $queryString = 'MATCH (n:Company) RETURN n UNION MATCH (n:Investor) RETURN n ';
        $data_raw = $this->executeQuery($queryString, []);
        $data = [];
        foreach ($data_raw as $data_row) {
            if ($data_row[0]) {
                $data[] = $data_row[0]->getProperties(); //Company
            }
        }
        return $data;
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
