<?php

namespace App\Api\V1\Controllers;

use App\Company;
use App\Http\Controllers\Controller;
use Everyman\Neo4j\Cypher\Query;
use Dingo\Api\Contract\Http\Request;
use Illuminate\Support\Collection;

class CompanyController extends Controller
{
    /**
     * Return json for "/companies" route call
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');

        $filters = $request->only(
            'industries',
            'legal-structures',
            'certifications',
            'impact-objective',
            'funding-round',
            'geography',
            'page',
            'show_path',
            'search',
            'per_page',
            'sort',
            'reviewed_only',
            'mechanism',
            'vehicle'
        );

        $perPage=(isset($filters["per_page"]))?$filters["per_page"]:100;
        $queryString = 'MATCH (company:Company) ';
        $whereAdded = false;
        $returnString = 'distinct(company) ';
        $params = [];

        $this->filterIfReviewedOnly($filters, $queryString, $whereAdded);
        $this->filterByAttributesQueryParams($filters, $queryString, $params, $whereAdded);
        $this->filterBySearchQueryParams($filters, $queryString, $params);
        $this->filterByImpactObjectiveQueryParams($filters, $queryString, $params);
        $this->filterByGeographyQueryParams($filters, $queryString, $params);
        $this->showPathQueryParams($filters, $queryString, $params, $returnString);

        $queryString .= 'RETURN '.$returnString;
        $queryString .= $this->getSortString($filters);

        $dataCompanies = $this->getCompanies($filters, $queryString, $params);
        $result = [
            "total"=> $dataCompanies['total'],
            "per_page"=> $perPage,
            "current_page"=> 1,
            "last_page"=> 1,
            "next_page_url"=> null,
            "prev_page_url"=> null,
            "from"=> 1,
            "to"=> $dataCompanies['total'],
            "data"  => $dataCompanies['companies']
        ];
        return $result;
    }

    /**
     * Update Query String if required reviewsed only data
     *
     * @param $filters
     * @param $queryString
     * @param $whereAdded
     */
    private function filterIfReviewedOnly($filters, &$queryString, &$whereAdded)
    {
        $reviewed_only=(isset($filters["reviewed_only"]))?$filters["reviewed_only"]:false;
        if ($reviewed_only=="true") {
            $queryString .= 'WHERE ';
            $queryString .= 'company.network_map_ready = "yes" ';
            $whereAdded = true;
        }
    }

    /**
     * Update Query String and params with attributes data
     *
     * @param $filters
     * @param $queryString
     * @param $params
     * @param $whereAdded
     */
    private function filterByAttributesQueryParams($filters, &$queryString, &$params, &$whereAdded)
    {
        $mapped_filters =[
            ['filter_name'=>'industries','attr_name'=>'industry'],
            ['filter_name'=>'legal-structures','attr_name'=>'legal_structure'],
            ['filter_name'=>'certifications','attr_name'=>'certifications']
        ];

        foreach ($mapped_filters as $mapped) {
            $this->setGenericQueryParams(
                $mapped['filter_name'],
                $mapped['attr_name'],
                $filters,
                $queryString,
                $params,
                $whereAdded
            );
        }
    }

    /**
     * Update Query String and params with search data
     *
     * @param $filters
     * @param $queryString
     * @param $params
     */
    private function filterBySearchQueryParams($filters, &$queryString, &$params)
    {
        $search = @$filters["search"];
        if (isset($search)&& !empty($search)) {
            $this->addWhereToQuery($queryString, $whereAdded);
            $queryString .= '(company.overview =~ {search} or company.name =~ {search}) ';
            $params["search"] = "(?i).*".$search.".*";
        }
    }

    /**
     * Update Query String and params with impact objective data
     *
     * @param $filters
     * @param $queryString
     * @param $params
     */
    private function filterByImpactObjectiveQueryParams($filters, &$queryString, &$params)
    {
        $filter_by_impact_objective = @$filters["impact-objective"];
        if (isset($filter_by_impact_objective)&& !empty($filter_by_impact_objective)) {
            if (!is_array($filter_by_impact_objective)) {
                $filter_by_impact_objective = [$filter_by_impact_objective];
            }
            $queryString .= 'MATCH (company)-->(impact:ImpactObjective) WHERE impact.slug in {impact_obj} ';
            foreach ($filter_by_impact_objective as $k => $fil_io) {
                $filter_by_impact_objective[$k]=str_slug($fil_io);
            }
            $params["impact_obj"] = $filter_by_impact_objective;
        }
    }

    /**
     * Update Query String and params with geography data
     *
     * @param $filters
     * @param $queryString
     * @param $params
     */
    private function filterByGeographyQueryParams($filters, &$queryString, &$params)
    {
        $filter_by_geography = @$filters["geography"];
        if (isset($filter_by_geography)&& !empty($filter_by_geography)) {
            if (!is_array($filter_by_geography)) {
                $filter_by_geography = [$filter_by_geography];
            }
            $queryString .= 'MATCH (company)-[:LOCATED_AT]->(:Office)-[:BELONGS_TO]->(geo:GeographicArea) ';
            $queryString .= 'WHERE geo.slug in {geo} ';
            foreach ($filter_by_geography as $k => $fil_geo) {
                $filter_by_geography[$k]=str_slug($fil_geo);
            }
            $params["geo"] = $filter_by_geography;
        }
    }

    /**
     * Update Query String and params if show path enabled
     *
     * @param $filters
     * @param $queryString
     * @param $params
     * @param $returnString
     */
    private function showPathQueryParams($filters, &$queryString, &$params, &$returnString)
    {
        $show_path = @$filters["show_path"];
        if (isset($show_path)&& !empty($show_path)) {
            $filter_by_funding_round = @$filters["funding-round"];
            $filter_by_mechanism = @$filters["mechanism"];
            $filter_by_vehicle = @$filters["vehicle"];

            $is_already_filtered = false;
            if (isset($filter_by_funding_round)&& !empty($filter_by_funding_round)) {
                if (!is_array($filter_by_funding_round)) {
                    $filter_by_funding_round = [$filter_by_funding_round];
                }
                $queryString .= 'MATCH (company:Company)<-[fund:FUNDED]-(investor) ';
                $queryString .= 'WHERE fund.round in {funding_round} ';
                $params["funding_round"] = $filter_by_funding_round;
                $is_already_filtered = true;
            }
            if (isset($filter_by_mechanism)&& !empty($filter_by_mechanism)) {
                if (!is_array($filter_by_mechanism)) {
                    $filter_by_mechanism = [$filter_by_mechanism];
                }
                $filter_by_mechanism = array_map('strtolower', $filter_by_mechanism);
                $queryString .= 'MATCH (company:Company)<-[fund:FUNDED]-(investor) ';
                $queryString .= 'WHERE fund.mechanism in {mechanism} ';
                $params["mechanism"] = $filter_by_mechanism;
                $is_already_filtered = true;
            }
            if (isset($filter_by_vehicle)&& !empty($filter_by_vehicle)) {
                if (!is_array($filter_by_vehicle)) {
                    $filter_by_vehicle = [$filter_by_vehicle];
                }
                $filter_by_vehicle = array_map('strtolower', $filter_by_vehicle);
                $queryString .= 'MATCH (company:Company)<-[fund:FUNDED]-(investor) ';
                $queryString .= 'WHERE fund.vehicle in {vehicle} ';
                $params["vehicle"] = $filter_by_vehicle;
                $is_already_filtered = true;
            }
            if (!$is_already_filtered) {
                $queryString .= 'OPTIONAL MATCH (company:Company)<-[fund:FUNDED]-(investor) ';
            }

            $returnString .= ',COLLECT(fund),COLLECT(investor)';
            $returnString .= ',COUNT(fund) as count_fund,SUM(toInt(fund.amount)) as sum_fund  ';
        }
    }

    /**
     * Return sort string
     *
     * @param $filters
     */
    private function getSortString($filters)
    {
        $sortStr="";
        $show_path = @$filters["show_path"];
        $sort=(isset($filters["sort"]))?$filters["sort"]:"sort_name_asc";
        if ($sort=="sort_name_desc") {
            $sortStr = "ORDER BY company.name DESC ";
        } elseif ($sort=="sort_name_asc") {
            $sortStr = "ORDER BY company.name ASC ";
        } elseif ($sort=="sort_investments_desc" && isset($show_path)) {
            $sortStr = "ORDER BY count_fund DESC ";
        } elseif ($sort=="sort_investments_asc" && isset($show_path)) {
            $sortStr = "ORDER BY count_fund ASC ";
        } elseif ($sort=="sort_total_amount_desc" && isset($show_path)) {
            $sortStr = "ORDER BY sum_fund DESC ";
        } elseif ($sort=="sort_total_amount_asc" && isset($show_path)) {
            $sortStr = "ORDER BY sum_fund ASC ";
        }

        return $sortStr;
    }

    /**
     * Return companies from Query String and Params
     *
     * @param $filters
     * @param $queryString
     * @param $params
     * @return array
     */
    private function getCompanies($filters, $queryString, $params)
    {
        $show_path = @$filters["show_path"];
        $companies_raw = $this->executeQuery($queryString, $params);
        $total = count($companies_raw);
        $companies = [];
        foreach ($companies_raw as $company_row) {
            $company_arr = $company_row[0]->getProperties(); //Company
            $company_obj = Collection::make([
                "name"=>$this->returnValidValue($company_arr, "name"),
                "slug"=>$this->returnValidValue($company_arr, "slug"),
                "id"=>$company_row[0]->getId()
            ]);
            if (isset($show_path)&& !empty($show_path)) {
                $company_funds_arr = [];
                $company_investors_arr = [];
                foreach ($company_row[1] as $funds) {
                    $company_funds_tmp = $funds->getProperties(); //Funds
                    $company_funds_arr[] = [
                        "amount" => $this->returnValidValue($company_funds_tmp, "amount"),
                        "id" => $funds->getId()
                    ];
                }
                foreach ($company_row[2] as $_investor) {
                    $company_investors_tmp = $_investor->getProperties(); //Investors
                    $company_investors_arr[] = [
                        "name" => $this->returnValidValue($company_investors_tmp, "name"),
                        "slug" => $this->returnValidValue($company_investors_tmp, "slug"),
                        "id" => $_investor->getId()
                    ];
                }
                $company_obj["total_funds"] = $company_row[3];
                $company_obj["sum_funds"] = $company_row[4];
                $company_obj["funds"] = Collection::make($company_funds_arr);
                $company_obj["investors"] = Collection::make($company_investors_arr);
            }
            $companies[] = $company_obj;
        }

        return ['total'=>$total, 'companies'=>Collection::make($companies)];
    }
    /**
     * Return Generic Query String and params data
     *
     * @param $filter_name
     * @param $attr_name
     * @param $filters
     * @param $queryString
     * @param $params
     * @param $whereAdded
     */
    private function setGenericQueryParams($filter_name, $attr_name, $filters, &$queryString, &$params, &$whereAdded)
    {
        $filter = @$filters[$filter_name];
        if (isset($filter)&& !empty($filter)) {
            $this->addWhereToQuery($queryString, $whereAdded);

            if (!is_array($filter)) {
                $filter = [$filter];
            }
            $queryString .= "company.$attr_name IN {".$attr_name."} ";
            $params[$attr_name] = $filter;
        }
    }

    /**
     * Update query string and whereAdded variable if needed
     *
     * @param $filter_name
     * @param $attr_name
     * @param $filters
     * @param $queryString
     * @param $params
     * @param $whereAdded
     */
    private function addWhereToQuery(&$queryString, &$whereAdded)
    {
        if (!$whereAdded) {
            $queryString .= 'WHERE ';
        } else {
            $queryString .= 'AND ';
        }
        $whereAdded = true;
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
     * Return json for "/company/{company_id}" route call
     *
     * @param  $company_id
     * @return array
     */
    public function show($company_id)
    {
        $result = array();
        $company = Company::find($company_id);
        if ($company) {
            $result = $company->toArray();
            unset($result["network_map_ready"]);
            $queryString = 'MATCH (company:Company)<-[fund:FUNDED]-(investor:Investor) 
                            WHERE id(company) = {company_id} 
                            RETURN company,fund,investor';
            $companies_raw = $this->executeQuery($queryString, ["company_id"=>intval($company_id)]);
            $result["investors"] = [];
            foreach ($companies_raw as $row) {
                $investor = $row[2]->getProperties();
                $investor["fund"] = $row[1]->getProperties();
                $result["investors"][$row[2]->getId()][] = $investor;
            }
        }
        return $result;
    }

    /**
     * Return json for search call
     *
     * @param  $company_id
     * @return array
     */
    public function search($search)
    {
        $result = array();
        if ($search) {
            $queryString = 'MATCH (company:Company) 
                            WHERE company.overview =~ \'(?i).*{search}.*\' 
                            OR company.name =~ \'(?i).*{search}.*\' 
                            RETURN company';
            $companies_raw = $this->executeQuery($queryString, ["search"=>intval($search)]);
            $result["investors"] = [];
            foreach ($companies_raw as $row) {
                $investor = $row[2]->getProperties();
                $investor["fund"] = $row[1]->getProperties();
                $result["investors"][$row[2]->getId()][] = $investor;
            }
        }
        return $result;
    }

    /**
     * Return json for "/companies/top_industries/{top_number}" route call
     *
     * @param  $top_number
     * @return array
     */
    public function topByIndustries($top_number)
    {
        $result = array();
        if (intval($top_number)) {
            $queryString = 'MATCH (:Investor)-[fund:FUNDED]->(company:Company) 
                            RETURN COUNT(fund) AS total, company.industry 
                            ORDER BY total DESC LIMIT {top_number}';
            $investors_raw = $this->executeQuery($queryString, ["top_number"=>intval($top_number)]);
            foreach ($investors_raw as $row) {
                $total = $row[0];
                $industry = $row[1];
                $result[] = ["total"=>$total,"industry"=>$industry];
            }
        }
        return $result;
    }

    /**
     * Return json for "/companies/top_funds_type/{top_number}" route call
     *
     * @param  $top_number
     * @return array
     */
    public function topFundsType($top_number)
    {
        $result = array();
        if (intval($top_number)) {
            $queryString = 'MATCH (:Investor)-[fund:FUNDED]->(:Company) 
                            RETURN SUM(toInt(fund.amount)) AS total, fund.round 
                            ORDER BY total DESC LIMIT {top_number}';
            $investors_raw = $this->executeQuery($queryString, ["top_number"=>intval($top_number)]);
            foreach ($investors_raw as $row) {
                $total = $row[0];
                $type = $row[1];
                $result[] = ["total"=>$total,"type"=>$type];
            }
        }
        return $result;
    }

    /**
     * Return json for "/companies/legal_structures" route call
     *
     * @return array
     */
    public function legalStructures()
    {
        $result = array();
        $queryString = 'MATCH (n:Company) OPTIONAL MATCH (n) 
                        WHERE EXISTS(n.legal_structure) 
                        RETURN count(distinct(id(n))) as count, n.legal_structure AS legal_structure 
                        ORDER BY count DESC';
        $investors_raw = $this->executeQuery($queryString, []);
        $totalLegalStructures = 0;
        $result["total"] = $totalLegalStructures;
        foreach ($investors_raw as $row) {
            $count = $row[0];
            $legal_name = $row[1]==null?"unknown":$row[1];
            $totalLegalStructures += $count;
            $result["data"][] = ["count"=>$count,"legal_structure"=>$legal_name];
        }
        $result["total"] = $totalLegalStructures;
        return $result;
    }

    /**
     * Return json for "/company_attributes" route call
     *
     * @return array
     */
    public function filterAttributes()
    {
        $data = [];

        $data["industries"] = $this->getIndustriesData();
        $data["legal_structures"] = $this->getLegalStructureData();
        $data["certifications"] = $this->getCertificationsData();
        $data["impact_objective"] = $this->getImpactObjectiveData();
        $data["funding_round"] = $this->getFundingRoundData();
        $data["mechanism"] = $this->getMechanismData();
        $data["vehicle"] = $this->getVehicleData();
        $data["geography"] = $this->getGeographyData();

        return $data;
    }

    /**
     * Return Industries Data
     *
     * @return array
     */
    private function getIndustriesData()
    {
        $queryString = 'UNWIND {data_arr} as list
                        OPTIONAL MATCH (n:Company) 
                        WHERE  n.industry in [list]
                        RETURN distinct(list) AS industry, count(distinct(n)) as total 
                        ORDER BY total desc';
        $params = ["data_arr"=>config('data_filters.industries')];
        return $this->getDataFromQuery($queryString, $params);
    }

    /**
     * Return Legal Structure Data
     *
     * @return array
     */
    private function getLegalStructureData()
    {
        $queryString = 'UNWIND {data_arr} as list
                        OPTIONAL MATCH (n:Company) 
                        WHERE  n.legal_structure in [list]
                        RETURN distinct(list) AS legal_structure, count(distinct(n)) as total 
                        ORDER BY total desc';
        $params = ["data_arr"=>config('data_filters.legal_structure')];
        return $this->getDataFromQuery($queryString, $params);
    }

    /**
     * Return Certifications Data
     *
     * @return array
     */
    private function getCertificationsData()
    {
        $queryString = 'UNWIND {data_arr} as list
                        OPTIONAL MATCH (n:Company) 
                        WHERE  n.certifications in [list]
                        RETURN distinct(list) AS certifications, count(distinct(n)) as total 
                        ORDER BY total desc';
        $certifications_raw = $this->executeQuery($queryString, [
            "data_arr"=>config('data_filters.certifications')
        ]);
        $certifications_arr = [];
        $count=1;
        foreach ($certifications_raw as $row) {
            $certifications_arr[$count."-".$row[1]] = $row[0];
            if (in_array($row[0], ["leed","bdih","ecocert"])) {
                $certifications_arr[$count."-".$row[1]] = strtoupper($row[0]);
            }
            if (in_array($row[0], ["usda organic"])) {
                $certifications_arr[$count."-".$row[1]] = "USDA organic";
            }

            $count++;
        }
        return $certifications_arr;
    }

    /**
     * Return Impact Objective Data
     *
     * @return array
     */
    private function getImpactObjectiveData()
    {
        $queryString = "MATCH (i:ImpactObjective) 
                        OPTIONAL MATCH (c:Company)-[:HAS_OBJECTIVE]->(i) 
                        RETURN distinct(i.slug) as slug, i.name as name, i.type as type, count(c) as count 
                        ORDER BY count desc";
        $impact_objective_raw = $this->executeQuery($queryString, []);
        $impact_objective = [];
        $count=1;
        foreach ($impact_objective_raw as $row) {
            $impact_objective["all"][$count."-".$row[3]]=$row[1];
            $impact_objective[$row[2]][$count."-".$row[3]]=$row[1];
            $count++;
        }
        return $impact_objective;
    }

    /**
     * Return Funding Round Data
     *
     * @return array
     */
    private function getFundingRoundData()
    {
        $queryString = "MATCH (c:Company)<-[f:FUNDED]-(:Investor) 
                        WHERE f.round is not null 
                        RETURN distinct(f.round), count(f) as total 
                        ORDER BY total DESC";
        $params = [];
        return $this->getDataFromQuery($queryString, $params);
    }

    /**
     * Return Mechanism Data
     *
     * @return array
     */
    private function getMechanismData()
    {
        $queryString = 'UNWIND {data_arr} as list
                        OPTIONAL MATCH (:Company)<-[f:FUNDED]-(:Investor) 
                        WHERE  f.mechanism in [list]
                        RETURN distinct(list) AS mechanism, count(distinct(f)) as total 
                        ORDER BY total desc';
        $mechanism_raw = $this->executeQuery($queryString, ["data_arr"=>config('data_filters.mechanism')]);
        $mechanism_arr = [];
        $count=1;
        foreach ($mechanism_raw as $row) {
            $mechanism_arr[$count."-".$row[1]] = (in_array($row[0], ["mri","pri"]))?strtoupper($row[0]):$row[0];
            $count++;
        }
        return $mechanism_arr;
    }

    /**
     * Return Vehicle Data
     *
     * @return array
     */
    private function getVehicleData()
    {
        $queryString = 'UNWIND {data_arr} as list
            OPTIONAL MATCH (:Company)<-[f:FUNDED]-(:Investor) WHERE  f.vehicle in [list]
            RETURN distinct(list) AS vehicle, count(distinct(f)) as total order by total desc';
        $params = ["data_arr"=>config('data_filters.vehicle')];
        return $this->getDataFromQuery($queryString, $params);
    }

    /**
     * Return Geography Data
     *
     * @return array
     */
    private function getGeographyData()
    {
        $queryString = 'MATCH (country:GeographicArea)-[:BELONGS_TO]->(region:GeographicArea) 
                        WHERE country.type = "country" 
                        OPTIONAL MATCH (c:Company)-[:LOCATED_AT]->(:Office)-[:BELONGS_TO]->(country)
                        RETURN country,region,count(c) as count 
                        ORDER BY count desc';
        $geography_raw = $this->executeQuery($queryString, []);
        $geography = [];
        $geography["global"] = ["name"=>"Global","regions"=>["all"=>["name"=>"All","countries"=>[]]]];
        $count=1;
        foreach ($geography_raw as $geo) {
            $country_arr = $geo[0]->getProperties();
            $region_arr = $geo[1]->getProperties();
            if (!isset($geography["global"]["regions"][$region_arr["slug"]])) {
                $geography["global"]["regions"][$region_arr["slug"]]=["name"=>$region_arr["name"],"countries"=>[]];
            }
            $geography["global"]["regions"][$region_arr["slug"]]["countries"][$count."-".$geo[2]]=[
                "name"=>$country_arr["name"]
            ];
            $geography["global"]["regions"]["all"]["countries"][$count."-".$geo[2]]=[
                "name"=>$country_arr["name"]
            ];
            $count++;
        }
        return $geography;
    }

    /**
     * Return Data from Query
     *
     * @return array
     */
    private function getDataFromQuery($queryString, $params = [])
    {
        $type_raw = $this->executeQuery($queryString, $params);
        $type_arr = [];
        $count=1;
        foreach ($type_raw as $row) {
            $type_arr[$count."-".$row[1]] = $row[0];
            $count++;
        }
        return $type_arr;
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
