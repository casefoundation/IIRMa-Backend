<?php

namespace App\Console\Commands;

use Everyman\Neo4j\Exception;
use App\Company;
use App\Investor;
use App\Office;
use App\GeographicArea;
use App\ImpactObjective;
use Illuminate\Support\Facades\File;

class AnalyzeImpactspace extends AnalyzeCommand
{
	
	protected $datasource = 'impactspace';
	
	protected $name = 'impactspace:analyze';
	
	protected $signature = 'impactspace:analyze 
        {--force_update} {--entity-type=} {--file=} {--file-type=} {--replace-data=}
    ';
	
	protected $description = 'Analyze data from Impactspace';
	
	
	public $certifications_mapping = [
		'B Corporation' => [],
		"Energy Star"=> [],
		"Rainforest Alliance Certified"=> [],
		"USDA Organic"=> [],
		"Other"=> [],
		"LEED"=> [],
		"Other Certification"=> [],
		"BDIH"=> [],
		"ECOCERT"=> [],
		"Fair Trade Certified"=> [],
		"Green Seal"=> [],
		"ignore" => [
			"B-Corp",
			"Other Personal Care Products",
			"B-Lab",
			"True"
		]
	];
	
	public $mechanism_mapping = [
		'other' => ["other mechanism"],
		'pri' => ["program related investment (pri)"],
		'mri' => ["mission related investment (mri)"],
	];
	public $vehicle_mapping = [
		'cash & convertible note' => ["cash & convertible notes"],
		'debt' => [],
		'equity' => [],
		'grants' => ["grant"],
		'guarantees' => [],
		'real assets' => [],
		'other' => ["other"],
		'ignore' => ["program related investment (pri)","mission related investment (mri)"]
	];
	
	public $legal_structures_mapping = [
		'other' => [
			"Other Legal Structure"
		],
		'limited liability company' => [
			"LLC",
			"llc"
		],
		"benefit corporation"=> [
			"Profit",
			"Project",
			"For-Benefit"
		]
	];
	
	public $investors_type_mapping = [
		'Corporate' => [],
		'Foundation/Endowment' => [
			"Foundation"
		],
		"Government"=> [],
		"Individual"=> [
			"Individual Investor"
		],
		"Institution/Bank"=> [
			"Institutional Investor",
			"Bank"
		],
		"Mass Retail"=> [],
		"Accelerator/Incubator"=> [],
		"Broker"=> [],
		"CDFI/DFI"=> [
			"CDFI",
			"DFI"
		],
		"Fund of Funds" => [
			"Funds of fund",
			"Funds",
			"Fund of Fund"
		],
		"Investment Fund"=> [
			"Investment Fund Manager",
			"Fund Manager"
		],
		"Investor Network"=> [
			"investor Network",
			"Investor"
		],
		"Wealth manager/Advisor"=> [],
		"Crowdfunding"=> [],
		"Other" => [
			"Other Capital Providers",
			"Other Capital Channels & Intermediaries"
		]
	];
	public $industries_mapping = [
		"ignore" => [
			"Any",
			"CivicX Fall 2016",
			"Education, 
            entrepreneurship, 
            materials, 
            maths, 
            nonprofit, 
            Schwab Foundation, 
            science,Social Entrepreneur 2015, 
            technology, 
            training",
			"N/A",
			"United States of America",
			"Unitus",
			"Accountability",
			"Base of the Pyramid/Extreme Affordability Design",
			"Capacity Building,Income/Productivity Growth",
			"Community Building,Local Food Distribution",
			"Elections",
			"Environment Conservation",
			"Equality and Empowerment",
			"Job Creation"
		],
		"energy" => [
			"Access to Energy,Capacity Building,Community Development",
			"Energy Efficiency",
			"Energy Storage, AGRION Disrupt",
			"Energy and Fuel Efficiency,Sustainable Energy",
			"Energy, Fuels & Generation",
			"Natural Resources Conservation,Sustainable Energy",
			"Sustainable Energy"
		],
		"Accommodation & food service" => [
			"Food Products & Organics",
			"Food Security,Health Improvement",
			"Tourism"
		],
		"Agriculture, forestry & fishing" => [
			"Agricultural",
			"Agriculture",
			"?Agriculture",
			"Sustainable Agriculture and Food Production",
			"Sustainable Oceans and Fisheries",
			"Sustainable Timber and Forestry"
		],
		"Other services"=>[
			"Apparel & Fashion, San Diego, Jewelry/Watches",
			"Green Consumer Products/Services",
			"Other Access to Basic Services",
			"Other Environmental Markets and Sustainable Assets",
			"Other Environmental Technology",
			"Other Financial Service",
			"Other Sustainable Consumer Products",
			"Property Rights",
			"Sustainable Consumer Products- Artisanal, Access to Basic Services-Education",
			"Sustainable-conservation"
		
		],
		"Education"=>[
			"College Retention, Behavioral Interventions, Mobile Nudges, Education"
		],
		"Human health & social work"=>[
			"Community Development,Health Improvement",
			"GSBI, Health Care, Healthcare Technology, Hospital, Mobile Technology, SEVC 2016",
			"Health Care Equipment and Services",
			"Rockhealth, Health, Health Services, Online Health",
			"Social Engagement"
		],
		"Construction" => [
			"Community Facilities/Infrastructure"
		],
		"financial & insurance activities" => [
			"Access to Financial Services",
			"Financial Services: Advisory Services",
			"Financial Services: Community Lending",
			"Financial Services: Microfinance",
			"Financial Services: Microinsurance",
			"Financial Services: Trade Finance",
			"Financial Services: SME Financing",
			"Conservation Finance",
			"Financing Fish, financingfish,Fish 2.0, SeaWeb, Sustainable Seafood",
			"Carbon Commodities"
		],
		"water & sewerage" => [
			"Water",
			"Water Quality & Rights Trading",
			"Water Quality and Rights Trading",
			"Water Technology",
			"Water",
		],
		"Real estate, design & building"=>[
			"Green Real Estate",
			"Real Estate",
			"Afordable Housing",
			"Biodiversity Conservation,Sustainable Land Use",
			"Housing/ Community Development",
			"Sustainable Land Use"
		],
		"Information, communication & technology"=>[
			"Media, Technology, Digital Access",
			"Technology Hardware and Equipment"
		],
		"Transportation & storage"=>[
			"Transportation"
		],
		"Waste Management & Recycling"=>[
			"Waste Management/Recycling",
			"Employment Services, Livelihood Building, Waste"
		],
		"Retail"=>[
			"Artisanal",
			"Artisanal Products",
			"Fair Trade Products"
		],
		"Professional & technical services"=>[
			"Materials Science",
			"Supply Chain Services"
		]
	];
	
	private $current_timestamp;
	private $replace_data;
	
	/**
	 *  Handle command import steps
	 */
	protected function handleImport()
	{
		
		// Retrieve a specific entity entity-type (company | finorg | deals )
		$entityType = $this->option('entity-type');
		if(empty($entityType)){
			$entityType = 'company,finorg';
		}
		
		// Retrieve a specific file
		$specificFile = $this->option('file');
		
		// Retrieve a specific file-type
		$specificFileType = $this->option('file-type');
		if(empty($specificFileType)){
			$specificFileType = 'csv';
		}
		
		
		$this->current_timestamp = time();
		$this->replace_data = !empty($this->option('replace-data')) ? $this->option('replace-data')=='yes' : true;
		
		$entityTypes = explode(',', $entityType);
		
		$map_types = ['company'=>'Company', 'finorg'=>'Investor', 'deals'=>'Deals'];
		$remove_analised = [];
		
		foreach($entityTypes as $type) {
			
			if(empty($specificFile)){
				if($type=='company'){
					// GET COMPANIES
					$companies_data_type = "csv";
					$companies_file = $this->pull("company/download", ['type' => $companies_data_type]);
					if ($companies_file) {
						$this->analyze($companies_file, "company", $companies_data_type);
						unlink($companies_file);
					}
				} else if($type=='finorg'){
					// GET INVESTORS
					$investors_data_type = "json";
					$investors_file = $this->pull("finorg/download", ['type' => $investors_data_type]);
					if ($investors_file) {
						$this->analyze($investors_file, "finorg", $investors_data_type);
						unlink($investors_file);
					}
				}
			} else {
				$this->analyze($specificFile, $type , $specificFileType);
			}
			
			if( $this->replace_data ) {
				$remove_analised[] = $map_types[$type];
			}
		}
		
		if( $this->replace_data ) {
			
			
			$remove_query_arr = [];
			foreach ($remove_analised as $type_analised) {
				$remove_query_arr[] = "e:" . $type_analised;
			}
			$remove_query = implode(' OR ', $remove_query_arr);
			
			// REMOVE RELATIONS
			$this->executeQuery("
				MATCH (e)
				OPTIONAL MATCH (e)-[r]-()
				WHERE 
					( e.last_existence_verification<>{current_timestamp} OR NOT EXISTS( e.last_existence_verification ) )
					AND 
					( $remove_query )
				DELETE r
			",
				["current_timestamp" => $this->current_timestamp]
			);
			
			// REMOVE NODES WITHOUT VERIFICATION
			$this->executeQuery(
				"
			MATCH (e) 
			WHERE 
				( e.last_existence_verification<>{current_timestamp} OR NOT EXISTS( e.last_existence_verification ) )
				AND 
				( $remove_query )
			DELETE e
			",
				["current_timestamp" => $this->current_timestamp]
			);
			
		}
	}
	
	/**
	 * Analyze data from pulled files from api
	 *
	 * @param string $file : specific filename of file to analyze
	 * @param string $type : type of data to analyze, values: company, finorg
	 * @param string $filetype : type of file to analyze, values: csv, json
	 * @return void
	 */
	private function analyze($file = "", $type = "company", $filetype = "csv", $show_progress = true)
	{
		if ($filetype=='csv') {
			$fileArr = $this->csvToArray($file);
		} elseif ($filetype=='json') {
			$fileArr = $this->jsonToArray($file, $type=='deals');
		}
		
		if ($fileArr!=null) {
			if (!$fileArr) {
				$fileArr = [];
			}
			try {
				$items_num = count($fileArr);
				$accum_time = 0;
				
				
				foreach ($fileArr as $i => $row) {
					$time_start = microtime(true);
					
					$this->proccessByType($type, (object)$row, $filetype);
					
					// PROGRESS DISPLAY
					$percent = ((float)$i / (float)count($fileArr));
					
					$time_end = microtime(true);
					$time_diff = $time_end - $time_start;
					$accum_time += $time_diff;
					
					$left_items = $items_num - ($i + 1);
					$avg_time = ($accum_time) / ($i + 1);
					$left_time = $left_items * $avg_time;
					
					$this->printAnalyzeProgress($show_progress, $percent, $left_time, $accum_time);
				}
				if ($show_progress) {
					$this->info("Data of \"$type\" analized successfully from Impactspace!," .
						" Total time analysing " . $this->timeFormat($accum_time) .
						"                                       ");
				}
			} catch (Exception $e) {
				echo 'Some error was occurred when processing data: "' . $e->getMessage() . '"' . "\n\r";
			}
		} else {
			if ($type!='deals') {
				$this->error("Data of \"$type\" was not loaded from Impactspace!, " .
					"                                       ");
			}
		}
	}
	
	/**
	 * Call process function for a specific type
	 *
	 * @param $type
	 * @param $row
	 * @param $filetype
	 * @return int
	 */
	private function proccessByType($type, $row)
	{
		$weight = 0;
		switch ($type) {
			case 'company': // COMPANY
				$weight = $this->processCompany($row);
				break;
			
			case 'finorg': // INVESTOR
				$weight = $this->processFinorg($row);
				break;
			case 'deals': // FUNDINGS
				$weight = $this->processDeals($row);
				break;
		}
		return $weight;
	}
	
	
	/**
	 * Print Analyze message
	 *
	 * @param $show_progress
	 * @param $percent
	 * @param $left_time
	 * @param $accum_time
	 */
	private function printAnalyzeProgress($show_progress, $percent, $left_time, $accum_time)
	{
		$total_chars = 30;
		if ($show_progress) {
			echo 'ANALYSING [' .
				$this->repeatString('=', $percent * $total_chars) .
				'>' . $this->repeatString(' ', $total_chars - ($percent * $total_chars)) .
				'] ' . ((int)($percent * 100)) . "% LAPSED " . $this->timeFormat($accum_time) .
				" LEFT " . $this->timeFormat($left_time) . " \r";
		}
	}
	
	
	/**
	 * Process Deals Information
	 * @param $row
	 * @param $filetype
	 * @return int
	 */
	private function processDeals($row)
	{
		$weight = 1;
		
		$investor = Investor::where('impactspace_id', '=', $row->fo_id)->first();
		$company = Company::where('impactspace_id', '=', $row->c_id)->first();
		if ($investor && $company) {
			$mapped_mechanism = $this->mappedMechanismDeal($row);
			$mapped_vehicle = $this->mappedVehicleDeal($row);
			
			$result = $this->investorFundCompany([
				"investor_id"   => $investor->id,
				"company_id"    => $company->id,
				"round"     => strtolower($row->round),
				"mechanism"     => strtolower($mapped_mechanism),
				"vehicle"     => strtolower($mapped_vehicle),
				"fund_date"     => $row->foi_date,
				"fund_amount"   => empty($row->foi_size)?0:$row->foi_size
			]);
		}
		
		return $weight;
	}
	
	
	/**
	 * Mapped Deal mechanism
	 * @param $row
	 * @return string
	 */
	private function mappedMechanismDeal($row)
	{
		$mapped_mechanism = strtolower($row->mechanism);
		foreach ($this->mechanism_mapping as $mapped => $value) {
			if (in_array($mapped_mechanism, $value)) {
				if ($mapped!='ignore') {
					$mapped_mechanism = $mapped;
					$this->createInvalidData(
						'company',
						'mechanism',
						$row->mechanism,
						'mechanism',
						$row->mechanism
					);
					break;
				} else {
					$mapped_mechanism ="";
				}
			}
		}
		return $mapped_mechanism;
	}
	
	/**
	 * Mapped Deal vehicle
	 * @param $row
	 * @return string
	 */
	private function mappedVehicleDeal($row)
	{
		$mapped_vehicle = strtolower($row->vehicle);
		foreach ($this->vehicle_mapping as $mapped => $value) {
			if (in_array($mapped_vehicle, $value)) {
				if ($mapped!='ignore') {
					$mapped_vehicle = $mapped;
					$this->createInvalidData(
						'company',
						'vehicle',
						$row->vehicle,
						'vehicle',
						$row->vehicle
					);
					break;
				} else {
					$mapped_vehicle ="";
				}
			}
			
			if (in_array(strtolower($row->vehicle), [
				"program related investment (pri)",
				"mission related investment (mri)"
			])) {
				// Try to map to mechanism, variables come wrong from api
				foreach ($this->mechanism_mapping as $mapped_mec => $value) {
					if (in_array($mapped_vehicle, $value)) {
						if ($mapped_mec!='ignore') {
							$mapped_vehicle = $mapped_mec;
							$this->createInvalidData(
								'company',
								'mechanism',
								$row->mechanism,
								'mechanism',
								$row->mechanism
							);
							break;
						} else {
							$mapped_vehicle = "";
						}
					}
				}
				break;
			}
		}
		return $mapped_vehicle;
	}
	
	/**
	 * function to analyze data from Investor
	 * @param array    $row
	 * @param string   $filetype
	 * @return int
	 */
	private function processFinorg($row)
	{
		$weight = 1;
		
		try {
			$investor = Investor::where('impactspace_id', '=', $row->id)->first();
			
			if (!$investor) {
				$investor = $this->createNewEntity($row, 'investor');
			}
			
			// CHECK IF UPDATED DATE CHANGED
			if ($investor->impactspace_updated_at != $row->last_updated || $this->force_update) {
				$this->updateEntity($row, $investor, 'investor');
			}
			
			$investor->last_existence_verification = $this->current_timestamp;
			$investor->save();
			
			if (isset($row->network_map_ready)) {
				$investor->network_map_ready = strtolower($row->network_map_ready);
				$investor->save();
			}
			// UPDATE INVESTOR VALUES
			
			$this->setInvestorType($row, $investor);
			$this->setEntityLegalStructure($row, $investor, 'investor');
			$weight = $this->setInvestorFunding($row, $weight);
			$this->setEntityOffices($row, 'investor');
			$weight = $this->setEntitySocial($row, 'investor', $investor, $weight);
			$weight = $this->setEntityEnviromental($row, 'investor', $investor, $weight);
		} catch (Exception $e) {
			$weight = 0;
			echo 'Some error was occurred when processing data: ' .
				$e->getMessage() . " on line ".$e->getLine() .
				", Details: \n" . $e->getTraceAsString() . "\n\r";
		}
		return $weight;
	}
	
	
	
	/**
	 * Set Investor Type
	 *
	 * @param $row
	 * @param $investor
	 */
	private function setInvestorType($row, $investor)
	{
		if (!empty($row->fo_type)) {
			if (empty($investor->type) ||
				$investor->impactspace_updated_at != $row->last_updated ||
				$this->force_update) {
				$mapped_type = trim($row->fo_type);
				foreach ($this->investors_type_mapping as $mapped => $value) {
					if (in_array(trim($row->fo_type), $value)) {
						$mapped_type = $mapped;
						$this->createInvalidData(
							'investor',
							'type',
							$investor->name,
							'fo_type',
							$row->fo_type,
							$row->id,
							$row->last_updated
						);
						break;
					}
				}
				try {
					$investor->type = $mapped_type;
					$investor->save();
				} catch (\Everyman\Neo4j\Exception $e) {
					//echo 'Some error was occurred when processing data: '.$e->getMessage()."\n\r";
					$this->createInvalidData(
						'investor',
						'type',
						$investor->name,
						'fo_type',
						$row->fo_type,
						$row->id,
						$row->last_updated
					);
				}
			}
		} else {
			$this->createInvalidData(
				'investor',
				'legal_structure',
				$investor->name,
				'fo_legal_structure',
				$row->fo_legal_structure,
				$row->id,
				$row->last_updated
			);
		}
	}
	
	/**
	 * Remove old fundings and generate new funding deals
	 *
	 * @param $row
	 * @param $investor
	 */
	private function setInvestorFunding($row, $weight)
	{
		if (!empty($row->funding)?count($row->funding)>0:false) {
			$weight += count($row->funding);
			// REMOVE EXISTENT RELATIONSHIPS
			$this->executeQuery(
				"
                        MATCH (i:Investor)-[r:FUNDED]->(c:Company)
                        WHERE i.impactspace_id={investor_id}
                        DELETE r
                        ",
				["investor_id"   => $row->id]
			);
			try {
				$deals_file_name = $this->pull(
					"deals",
					['finorg'=> urlencode($row->fo_name)],
					"",
					false
				);
				if ($deals_file_name) {
					$this->analyze($deals_file_name, "deals", 'json', false);
					unlink($deals_file_name);
				}
			} catch (Exception $e) {
				echo 'Some error was occurred when processing data: '.$e->getMessage()."\n\r";
			}
		}
		return $weight;
	}
	
	/**
	 * Return Geographic Area ID from a specific Country
	 * @param $country
	 * @param $office
	 * @param $address
	 * @return int
	 */
	private function getGeoAreaId($office, $country_field)
	{
		$geo_area_id = 0;
		$mapped_country = $office->$country_field;
		
		foreach ($this->geographic_areas_mapping as $mapped => $value) {
			if (in_array($office->$country_field, $value)) {
				$mapped_country = $mapped;
				$this->createInvalidData(
					'office',
					'country',
					$office->address,
					$country_field,
					$office->$country_field
				);
				break;
			}
		}
		
		$geo_area = GeographicArea::where('name', '=', $mapped_country)->first();
		
		if ($geo_area) {
			$geo_area_id = $geo_area->id;
		} else {
			$this->createInvalidData(
				'office',
				'mapped_country',
				$office->address,
				$country_field,
				$mapped_country
			);
		}
		
		return $geo_area_id;
	}
	
	
	/**
	 * function to analyze data from company
	 *
	 * @param array    $row
	 * @param string   $filetype
	 * @return int
	 */
	public function processCompany($row)
	{
		$weight = 1;
		
		try {
			$company = Company::where('impactspace_id', '=', $row->id)->first();
			
			if (!$company) {
				$company = $this->createNewEntity($row, 'company');
			}
			
			// CHECK IF UPDATED DATE CHANGED
			if ($company->impactspace_updated_at != $row->last_updated || $this->force_update) {
				$this->updateEntity($row, $company, 'company');
			}
			
			$company->last_existence_verification = $this->current_timestamp;
			$company->save();
			
			if (isset($row->network_map_ready)) {
				$company->network_map_ready = strtolower($row->network_map_ready);
				$company->save();
			}
			
			// UPDATE COMPANY VALUES
			$this->setEntityLegalStructure($row, $company, 'company');
			$this->setCompanyCertifications($row, $company);
			$this->setCompanyIndustry($row, $company);
			$this->setEntityOffices($row, 'company');
			$weight = $this->setEntitySocial($row, 'company', $company, $weight);
			$weight = $this->setEntityEnviromental($row, 'company', $company, $weight);
		} catch (Exception $e) {
			$weight = 0;
			echo print_r($row, true) .
				'Some error was occurred when processing data: ' . $e->getMessage() .
				" on line " . $e->getLine() . ", Details: \n" . $e->getTraceAsString() . "\n\r";
		}
		
		return $weight;
	}
	
	
	/**
	 * Create new entity with specific type
	 *
	 * @param $row
	 * @param $type
	 * @return null|static
	 */
	private function createNewEntity($row, $type)
	{
		$prefix = '';
		if ($type=='company') {
			$prefix = 'c';
		}
		if ($type=='investor') {
			$prefix = 'fo';
		}
		
		$entity_params = array();
		$slug = $this->createEntitySlug($row, $type);
		
		$entity_mapping = $this->createEntityMapping($row, $prefix, $slug, $type);
		
		$entity_data = $this->groupEntityData(
			$type,
			'top_level',
			$row->{$prefix.'_name'},
			$row->id,
			$row->last_updated
		);
		foreach ($entity_mapping as $mapping) {
			$entity_params = $this->setIndexToArray(
				$entity_params,
				$mapping['index'],
				$mapping['value'],
				$entity_data
			);
		}
		
		$entity = $this->createEntityObjectWithParams($entity_params, $slug, $type);
		
		return $entity;
	}
	
	
	/**
	 * Generate Entity Slug
	 *
	 * @param $row
	 * @param $type
	 * @return string
	 */
	private function createEntitySlug($row, $type)
	{
		$slug = '';
		if ($type=='company') {
			$slug = str_slug($row->c_name);
			$company = Company::where('slug', '=', $slug)->first();
			if ($company) {
				$slug = str_slug($row->id.' '.$row->c_name);
			}
		}
		if ($type=='investor') {
			$slug = str_slug($row->fo_name);
			$investor = Investor::where('slug', '=', $slug)->first();
			if ($investor) {
				$slug = str_slug($row->id.' '.$row->fo_name);
			}
		}
		
		return $slug;
	}
	
	/**
	 * Create entity mapping array
	 *
	 * @param $row
	 * @param $prefix
	 * @param $slug
	 * @param $type
	 * @return array
	 */
	private function createEntityMapping($row, $prefix, $slug, $type)
	{
		$entity_mapping = [
			['index'=>'name', 'value'=>$row->{$prefix.'_name'}],
			['index'=>'slug', 'value'=>$slug],
			['index'=>'impactspace_id', 'value'=>$row->id],
			['index'=>'impactspace_updated_at', 'value'=>$row->last_updated],
			['index'=>'overview', 'value'=>$row->{$prefix.'_overview'}],
			['index'=>'mission_statement', 'value'=>$row->{$prefix.'_mission_statement'}],
			['index'=>'email', 'value'=>$row->{$prefix.'_email'}],
			['index'=>'facebook', 'value'=>$row->{$prefix.'_facebook'}],
			['index'=>'twitter', 'value'=>$row->{$prefix.'_twitter_username'}],
			['index'=>'linkedin', 'value'=>$row->{$prefix.'_linkedin'}],
			['index'=>'phone', 'value'=>$row->{$prefix.'_phone_number'}],
			['index'=>'founded_date', 'value'=>$row->{$prefix.'_founded_date'}],
			['index'=>'external_links', 'value'=>$row->external_links],
			['index'=>'impactspace_updated_at', 'value'=>$row->last_updated],
		];
		if ($type=='company') {
			$entity_mapping[] = ['index'=>'website', 'value'=>$row->c_homepage_url];
			$entity_mapping[] = ['index'=>'number_of_employees', 'value'=>$row->number_of_employees];
		} elseif ($type=='investor') {
			$entity_mapping[] = ['index'=>'website', 'value'=>$row->fo_homepage];
			$entity_mapping[] = ['index'=>'number_of_employees', 'value'=>$row->fo_number_of_members];
		}
		if (isset($row->network_map_ready)) {
			$entity_mapping[] = ['index'=>'network_map_ready', 'value'=>strtolower($row->network_map_ready)];
		}
		return $entity_mapping;
	}
	
	
	/**
	 * Create NeoEloquent Entity Model
	 *
	 * @param $entity_params
	 * @param $slug
	 * @param $type
	 * @return null|static
	 */
	private function createEntityObjectWithParams($entity_params, $slug, $type)
	{
		$entity = null;
		if ($type=='company') {
			$created_company = Company::create($entity_params);
			$entity = Company::where('slug', '=', $slug)->first();
			if (!$entity) {
				$entity = $created_company;
			}
		} elseif ($type=='investor') {
			$created_investor = Investor::create($entity_params);
			$entity = Investor::where('slug', '=', $slug)->first();
			if (!$entity) {
				$entity = $created_investor;
			}
		}
		return $entity;
	}
	
	/**
	 * Update entity with row values
	 * @param $row
	 * @param \Vinelab\NeoEloquent\Eloquent\Model $entity
	 * @param $type
	 */
	private function updateEntity($row, \Vinelab\NeoEloquent\Eloquent\Model $entity, $type)
	{
		$prefix = '';
		if ($type=='company') {
			$prefix = 'c';
		} elseif ($type=='investor') {
			$prefix = 'fo';
		}
		
		$entity_mapping = [
			['index'=>'name', 'value'=>$row->{$prefix.'_name'}],
			['index'=>'overview', 'value'=>$row->{$prefix.'_overview'}],
			['index'=>'mission_statement', 'value'=>$row->{$prefix.'_mission_statement'}],
			['index'=>'email', 'value'=>$row->{$prefix.'_email'}],
			['index'=>'facebook', 'value'=>$row->{$prefix.'_facebook'}],
			['index'=>'twitter', 'value'=>$row->{$prefix.'_twitter_username'}],
			['index'=>'linkedin', 'value'=>$row->{$prefix.'_linkedin'}],
			['index'=>'phone', 'value'=>$row->{$prefix.'_phone_number'}],
			['index'=>'founded_date', 'value'=>$row->{$prefix.'_founded_date'}],
			['index'=>'external_links', 'value'=>$row->external_links],
			['index'=>'impactspace_updated_at', 'value'=>$row->last_updated],
		];
		if ($type=='company') {
			$entity_mapping[] = ['index'=>'website', 'value'=>$row->c_homepage_url];
			$entity_mapping[] = ['index'=>'number_of_employees', 'value'=>$row->number_of_employees];
		} elseif ($type=='investor') {
			$entity_mapping[] = ['index'=>'website', 'value'=>$row->fo_homepage];
			$entity_mapping[] = ['index'=>'number_of_employees', 'value'=>$row->fo_number_of_members];
		}
		
		if (isset($row->network_map_ready)) {
			$investors_mapping[] = ['index'=>'network_map_ready', 'value'=>strtolower($row->network_map_ready)];
		}
		
		foreach ($entity_mapping as $mapping) {
			$this->setIndexToObject(
				$entity,
				$mapping['index'],
				$mapping['value'],
				$type,
				'top_level',
				$row->{$prefix.'_name'},
				$row->id,
				$row->last_updated
			);
		}
		
		$entity->save();
	}
	
	/**
	 * Set Entity Legal Structure
	 *
	 * @param $row
	 * @param $entity
	 * @param $type
	 */
	private function setEntityLegalStructure($row, $entity, $type)
	{
		$type_prefix = '';
		if ($type=='country') {
			$type_prefix = 'c';
		} elseif ($type=='investor') {
			$type_prefix = 'fo';
		}
		if ( property_exists( $row , $type_prefix.'_legal_structure' ) ) {
			if( !empty($row->{$type_prefix.'_legal_structure'}) ){
				if (empty($entity->legal_structure) ||
					$entity->impactspace_updated_at != $row->last_updated || $this->force_update) {
					$mapped_legal_structure = $row->{$type_prefix.'_legal_structure'};
					foreach ($this->legal_structures_mapping as $mapped => $value) {
						if (in_array($row->{$type_prefix.'_legal_structure'}, $value)) {
							$mapped_legal_structure = $mapped;
							$this->createLegalStructureInvalidData($row, $entity, $type_prefix);
							break;
						}
					}
					try {
						$entity->legal_structure = strtolower($mapped_legal_structure);
						$entity->save();
					} catch (Exception $e) {
						$this->createLegalStructureInvalidData($row, $entity, $type_prefix);
					}
				}
			} else {
				$this->createLegalStructureInvalidData($row, $entity, $type_prefix);
			}
		} else {
			$this->createLegalStructureInvalidData($row, $entity, $type_prefix);
		}
	}
	
	
	/**
	 * Generate Legal Structure Invalid Data
	 *
	 * @param $row
	 * @param $entity
	 * @param $type_prefix
	 */
	private function createLegalStructureInvalidData($row, $entity, $type_prefix)
	{
		$this->createInvalidData(
			'company',
			'legal_structure',
			$entity->name,
			$type_prefix.'_legal_structure',
			property_exists( $row , $type_prefix.'_legal_structure' ) ? $row->{$type_prefix.'_legal_structure'} : 'legal structure not set',
			$row->id,
			$row->last_updated
		);
	}
	
	/**
	 * Set Company Certifications
	 *
	 * @param $row
	 * @param $company
	 */
	private function setCompanyCertifications($row, $company)
	{
		if ( property_exists( $row , 'c_certifications' ) ) {
			if (!empty($row->c_certifications)) {
				if (empty($company->c_certifications) || $company->impactspace_updated_at != $row->last_updated) {
					$certifications_structure = $row->c_certifications;
					foreach ($this->certifications_mapping as $mapped => $value) {
						if (in_array($row->c_certifications, $value)) {
							$certifications_structure = $mapped;
							$this->createInvalidData(
								'company',
								'certifications',
								$company->name,
								'c_certifications',
								$row->c_certifications,
								$row->id,
								$row->last_updated
							);
							break;
						}
					}
					try {
						if ($certifications_structure != 'ignore') {
							$company->certifications = strtolower($certifications_structure);
							$company->save();
						}
					} catch (Exception $e) {
						//echo 'Some error was occurred when processing data: '.$e->getMessage()."\n\r";
						$this->createInvalidData(
							'company',
							'c_certifications',
							$company->name,
							'c_certifications',
							$row->c_certifications,
							$row->id,
							$row->last_updated
						);
					}
				}
			} else {
				$this->createInvalidData(
					'company',
					'c_certifications',
					$company->name,
					'c_certifications',
					$row->c_certifications,
					$row->id,
					$row->last_updated
				);
			}
		}
	}
	
	/**
	 * Set Company Industry
	 * @param $row
	 * @param $company
	 */
	private function setCompanyIndustry($row, $company)
	{
		if ( property_exists( $row , 'industry' ) ) {
			if (!empty($row->industry)) {
				if (empty($company->industry) || $company->impactspace_updated_at != $row->last_updated) {
					$mapped_industry = $row->industry;
					foreach ($this->industries_mapping as $mapped => $value) {
						if (in_array($row->industry, $value)) {
							$mapped_industry = $mapped;
							$this->createInvalidData(
								'company',
								'mapped_industries',
								$company->name,
								'c_sector_activities',
								$row->industry,
								$row->id,
								$row->last_updated
							);
							break;
						}
					}
					try {
						if ($mapped_industry != 'ignore') {
							$company->industry = strtolower($mapped_industry);
							$company->save();
						}
					} catch (Exception $e) {
						//echo 'Some error was occurred when processing data: '.$e->getMessage()."\n\r";
						$this->createInvalidData(
							'company',
							'industries',
							$company->name,
							'c_sector_activities',
							$row->industry,
							$row->id,
							$row->last_updated
						);
					}
				}
			} else {
				$this->createInvalidData(
					'company',
					'industries',
					$company->name,
					'c_sector_activities',
					$row->industry,
					$row->id,
					$row->last_updated
				);
			}
		}
	}
	/**
	 * Set Entity Offices
	 *
	 * @param $row
	 * @param $ytpe
	 */
	private function setEntityOffices($row, $type)
	{
		if ( property_exists( $row , 'Offices' ) ) {
			if ( (!empty($row->Offices) && is_array($row->Offices)) ? count($row->Offices) > 0 : false) {
				$type_prefix = '';
				if ($type == 'country' || $type=='company') {
					$type_prefix = 'co';
				} elseif ($type == 'investor') {
					$type_prefix = 'foo';
				}
				foreach ($row->Offices as $office) {
					$existing_office = Office::where('address', '=', $office->address)
						->where('city', '=', $office->city)
						->where('postal_code', '=', $office->{$type_prefix . '_postal_code'})
						->first();
					$geo_area_id = 0;
					if (!$existing_office) {
						$office_params = [];
						$hq = array("head office", "headquarters", "headquarter", "hq");
						$investors_mapping = [
							['index' => 'address', 'value' => $office->address],
							['index' => 'city', 'value' => $office->city],
							['index' => 'postal_code', 'value' => $office->{$type_prefix . '_postal_code'}],
						];
						if (in_array(strtolower($office->{$type_prefix . '_description'}), $hq)) {
							$investors_mapping[] = ['index' => 'headquarter', 'value' => 1];
						}
						
						
						if (!empty($office->{$type_prefix . '_country'})) {
							$geo_area_id = $this->getGeoAreaId(
								$office,
								$type_prefix . '_country'
							);
						}
						
						$existing_office = Office::create($office_params);
					}
					if ($geo_area_id) {
						$this->connectOfficeWithGeographicArea($row, $type, $geo_area_id, $existing_office);
					}
				}
			}
		}
	}
	
	
	/**
	 * Connect offices from different entity types
	 *
	 * @param $row
	 * @param $type
	 * @param $geo_area_id
	 * @param $existing_office
	 */
	private function connectOfficeWithGeographicArea($row, $type, $geo_area_id, $existing_office)
	{
		$params = [
			'office_id'     => $existing_office->id,
			$type.'_id'   => $row->id,
			'geographic_id'   => $geo_area_id,
		];
		if ($type=='company') {
			Office::companyLocatedAtOffice($params);
		} elseif ($type=='investor') {
			Office::investorLocatedAtOffice($params);
		}
	}
	
	/**
	 * Set Entity Social Objective Attribute
	 * @param $row
	 * @param $type
	 * @param $entity
	 * @param $weight
	 * @return int
	 */
	private function setEntitySocial($row, $type, $entity, $weight)
	{
		$social_objectives = $row->social_objectives;
		if (!empty($social_objectives)) {
			$social_objectives_arr = explode(',', $social_objectives);
			$weight+= count($social_objectives_arr);
			
			foreach ($social_objectives_arr as $social_objective) {
				$impact_objective = ImpactObjective::where('name', '=', $social_objective)->first();
				if (!$impact_objective) {
					$this->createInvalidData(
						$type,
						'impact_objective',
						$entity->name,
						'social_objective',
						$social_objective,
						$row->id,
						$row->last_updated
					);
				} else {
					$this->linkEntitiesWithImpactObjective($type, 'social', $entity, $impact_objective);
				}
			}
		}
		return $weight;
	}
	
	/**
	 * Set Entity Enviromental Objective Attribute
	 * @param $row
	 * @param $type
	 * @param $entity
	 * @param $weight
	 * @return int
	 */
	private function setEntityEnviromental($row, $type, $entity, $weight)
	{
		if ($type=="company") {
			$env_objectives = $row->env_objectives; //type == "company"
		} elseif ($type=="investor") {
			$env_objectives = $row->environmental_objectives;
		}
		if (!empty($env_objectives)) {
			$env_objectives_arr = explode(',', $env_objectives);
			$weight+= count($env_objectives_arr);
			
			foreach ($env_objectives_arr as $env_objective) {
				$impact_objective = ImpactObjective::where('name', '=', $env_objective)->first();
				if (!$impact_objective) {
					$this->createInvalidData(
						$type,
						'impact_objective',
						$entity->name,
						'env_objective',
						$env_objective,
						$row->id,
						$row->last_updated
					);
				} else {
					$this->linkEntitiesWithImpactObjective($type, 'environmental', $entity, $impact_objective);
				}
			}
		}
		return $weight;
	}
	
	
	/**
	 * Link entities to impact objective
	 * @param $type
	 * @param $objective_type
	 * @param $entity
	 * @param $impact_objective
	 */
	private function linkEntitiesWithImpactObjective($type, $objective_type, $entity, $impact_objective)
	{
		$params = [
			'objective_id'  =>  $impact_objective->id,
			'objective_type'=> 'environmental',
			$type.'_id'   =>  empty($entity->id) ? $entity->slug : $entity->id
		];
		if ($type=='company') {
			$this->objectiveBelongsToCompany($params);
		} elseif ($type=='investor') {
			$this->objectiveBelongsToInvestor($params);
		}
	}
	
	/**
	 * Return result of query execution
	 *
	 * @param array  $params
	 * @return object
	 */
	public function objectiveBelongsToInvestor($params)
	{
		$queryString = '
            MATCH (i:ImpactObjective), (n:Investor)
            WHERE ID(i) = {objective_id} AND i.type = {objective_type} AND ID(n) = {investor_id}
            CREATE UNIQUE (i)<-[:HAS_OBJECTIVE]-(n)
        ';
		
		return $this->executeQuery($queryString, $params);
	}
	
	/**
	 * Return result of query execution
	 *
	 * @param array  $params
	 * @return object
	 */
	public function objectiveBelongsToCompany($params)
	{
		$queryString = '
            MATCH (i:ImpactObjective), (c:Company)
            WHERE ID(i) = {objective_id} AND i.type = {objective_type} AND ID(c) = {company_id}
            CREATE UNIQUE (i)<-[:HAS_OBJECTIVE]-(c)
        ';
		
		return $this->executeQuery($queryString, $params);
	}
	
	/**
	 * Return result of query execution
	 *
	 * @param array  $params
	 * @return object
	 */
	public function companyBelongsGeographicArea($params)
	{
		$queryString = '
            MATCH (g:GeographicArea), (c:Company)
            WHERE g.name = {geographic_area_name} AND ID(c) = {company_id}
            CREATE UNIQUE (g)<-[:BELONGS_TO]-(c)
        ';
		return $this->executeQuery($queryString, $params);
	}
	
	/**
	 * Return result of query execution
	 *
	 * @param array  $params
	 * @return object
	 */
	public function investorFundCompany($params)
	{
		$round = '';
		$mechanism = '';
		$vehicle = '';
		$fund_date = '';
		
		if (!empty($params['round'])) {
			$round = 'round: {round},';
		} else {
			unset($params['round']);
		}
		
		if (!empty($params['mechanism'])) {
			$mechanism = 'mechanism: {mechanism},';
		} else {
			unset($params['mechanism']);
		}
		
		if (!empty($params['vehicle'])) {
			$vehicle = 'vehicle: {vehicle},';
		} else {
			unset($params['vehicle']);
		}
		
		if (!empty($params['fund_date'])) {
			$fund_date = 'date: {fund_date},';
		} else {
			unset($params['fund_date']);
		}
		
		$queryString = "
            MATCH (i:Investor), (c:Company)
            WHERE ID(i) = {investor_id} AND ID(c) = {company_id}
            CREATE UNIQUE (c)<-[:FUNDED { $fund_date $mechanism $vehicle $round amount: {fund_amount}}]-(i)
        ";
		
		return $this->executeQuery($queryString, $params);
	}
	
	/**
	 * Return normalized array from json file
	 *
	 * @param string $filename
	 * @return array
	 */
	public function jsonToArray($filename = '', $html = false)
	{
		if (!file_exists($filename) || !is_readable($filename)) {
			return false;
		}
		
		$json_str = file_get_contents($filename);
		$json_decode = json_decode($json_str);
		
		if (json_last_error()!=JSON_ERROR_NONE) {
			$json_str = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $json_str);
			$json_str = preg_replace('/\"(.*?)<(.*?)\"/', '"$1&lt;$2"', $json_str);
			$json_str = preg_replace('/\"(.*?)>∫(.*?)\"/', '"$1&gt;$2"', $json_str);
			$json_str = strip_tags($json_str);
			
			if (!$html) {
				$json_str = str_replace('": "', "\": {}{}", $json_str);
				$json_str = trim(preg_replace('/\"\,\n/', "{}{},\n", $json_str));
				$json_str = trim(preg_replace('/\"\n/', "{}{}\n", $json_str));
				$json_str = trim(preg_replace('/\n\"/', "\n{}{}", $json_str));
				$json_str = preg_replace_callback("/\"+(\w)+\":/", array(&$this, 'replaceQuotes'), $json_str);
				
				$json_str = str_replace('"', "'", $json_str);
				
				$json_str = str_replace('{}{}', '"', $json_str);
				
				//$json_str = preg_replace_callback("/\"http+(.*)+\"/", array(&$this, 'scapeUrls'), $json_str);
			}
			
			
			$json_str = str_replace('\\"', "\\'", $json_str);
			$json_str = str_replace('\\\\', "\\", $json_str);
			$json_str = str_replace('\\"', "\\'", $json_str);
			$json_str = str_replace('\\\\', "\\", $json_str);
			$json_str = preg_replace('/\"\s+\"/', '""', $json_str);
			$json_str = preg_replace('/\"\n+\"/', '""', $json_str);
			$json_str = preg_replace('/\"http(.*?)\n(.*?)\"/', '"http$1$2\"', $json_str);
			$json_str = preg_replace('/: \"  \"\,/', ' ', $json_str);
			$json_str = trim(preg_replace('/\s+/', ' ', $json_str));
			$json_str = trim(preg_replace('/\t+/', '', $json_str));
			
			$json_str = utf8_encode($json_str);
			
			if ($html) {
				$json_str = str_replace(', }', "}", $json_str);
			}
			$json_decode = json_decode(trim($this->removeBOM(stripslashes($json_str))), false);
		}
		
		return $json_decode;
	}
	
	/**
	 * Return normalized array from csv file
	 *
	 * @param string $filename
	 * @param string $delimiter
	 * @return array
	 */
	public function csvToArray($filename = '', $delimiter = ',')
	{
		if (!file_exists($filename) || !is_readable($filename)) {
			return false;
		}
		
		$header = null;
		$data = array();
		
		if (($handle = fopen($filename, 'r')) !== false) {
			while (($row = fgetcsv($handle, 1500, $delimiter)) !== false) {
				if (!$header) {
					$header = $row;
				} else {
					if (count($row)==count($header)) {
						$data[] = array_combine($header, $row);
					}
				}
			}
			fclose($handle);
		}
		
		return $data;
	}
	
	/**
	 * Pull data from impactspace api
	 *
	 * @param string $edpoint : specific endpoint to pull data values: company, finorg, suporg
	 * @param string $format : format to pull data from api, values: csv, json
	 * @param string $file_target : specific file target, automatically created if blank
	 * @return string $file_name
	 */
	private function pull($endpoint = "company", $params = null, $file_target = "", $show_progress = true)
	{
		$timestamp = date('U');
		$type = isset($params['type']) ? ('.' . $params['type']) : '';
		$file_name = empty($file_target) ? "impactspace-$timestamp$type" : $this->argument('file_target');
		
		if (!File::exists(storage_path('uploads/'))) {
			File::makeDirectory(storage_path('uploads/'));
		}
		
		$path = storage_path('uploads/' . $file_name);
		$content_type = $this->getPullContentType($params);
		$url = $this->generatePullUrl($endpoint, $params);
		
		//echo $url."\n\r";
		
		if ($show_progress) {
			echo "Downloading data from $endpoint...\n\r";
		}
		
		$fp = fopen($path, 'w+');//This is the file where we save the information
		$ch = curl_init($url);//Here is the file we are downloading
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, $show_progress ? 'progress' : 'no_progress'));
		curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 1); // None header
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1); // Binary transfer 1
		curl_setopt($ch, CURLOPT_HEADER, false);// remote header from response
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( "Content-Type: $content_type; charset=UTF-8")
		); // get file in correct format
		
		try {
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			
			return $path;
			if ($show_progress) {
				$this->info("Data from \"$endpoint/\" pulled successfully from Impactspace!    " .
					"                                    ");
			}
			die;
		} catch (Exception $e) {
			return false;
		}
	}
	
	/**
	 * Return contant type based on params
	 *
	 * @param $params
	 * @return string
	 */
	private function getPullContentType($params)
	{
		$content_type = 'application/json';
		if ($params) {
			if (isset($params['type'])) {
				switch ($params['type']) {
					case 'csv':
						$content_type = 'text/csv';
						break;
					case 'json':
						$content_type = 'application/json';
						break;
				}
			}
		}
		return $content_type;
	}
	
	/**
	 * Return api service url string
	 *
	 * @param $endpoint
	 * @param $params
	 * @param $content_type
	 * @return string
	 */
	private function generatePullUrl($endpoint, $params)
	{
		$api_key   = config('impactspace.user_key');
		if (empty($api_key)) {
			print_r("Please make sure you have the variable IMPACTSPACE_KEY on your /.env file setted\n\r");
			print_r("Then run the followin commands:\n\r");
			print_r("php artisan config:clear\n\r");
			print_r("php artisan cache:clear\n\r");
			die;
		}
		$params_str = '';
		
		if ($params) {
			$params_arr = [];
			foreach ($params as $key => $val) {
				$params_arr[] = "$key=$val";
			}
			$params_str = implode('&', $params_arr);
		}
		
		$url       = "http://impactspace.com/api/$endpoint?$params_str&key=$api_key";
		return $url;
	}
	
	/**
	 * Show progress on console if the download_size var is available
	 *
	 * @return void
	 */
	public function progress($resource, $download_size, $downloaded, $upload_size, $uploaded)
	{
		if ($download_size > 0) {
			$total_chars = 30;
			$percent = ((float)$downloaded/(float)$download_size);
			echo 'DOWNLOADING [' . $this->repeatString('=', $percent*$total_chars).'>' .
				$this->repeatString(' ', $total_chars-($percent*$total_chars)) . '] ' .
				((int)($percent*100)) . "% \r";
		}
	}
	public function no_progress($resource, $download_size, $downloaded, $upload_size, $uploaded){
		
	}
}
