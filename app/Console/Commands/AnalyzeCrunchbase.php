<?php namespace App\Console\Commands;

use App\GeographicArea;
use App\Office;
use Everyman\Neo4j\Exception;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use App\Company;
use App\Investor;

class AnalyzeCrunchbase extends AnalyzeCommand
{

    protected $datasource = 'crunchbase';

    protected $name = 'crunchbase:analyze';

    protected $signature = 'crunchbase:analyze 
        {--use_cached_download} 
        {--ignore_offices} 
        {--use_cached_csv} 
        {--force_update}
    ';

    protected $description = 'Analyze data from Crunchbase';

    protected $api_url = "https://api.crunchbase.com/v3.1";

    /**
     *  Handle command import steps
     */
    protected function handleImport()
    {
        $useCachedCsv = !empty($this->option('use_cached_csv'));
        $useCachedDownload = !empty($this->option('use_cached_download')) || $useCachedCsv ;
        $ignoreOffices= !empty($this->option('ignore_offices')) || $useCachedCsv ;

        $tarFile = $this->downloadData($useCachedDownload);
        ini_set('memory_limit', '512M');
        $csvUrl = $this->extractAndCleanCsv($tarFile, 'organizations', $useCachedCsv);
        $this->analyzeData($csvUrl, $ignoreOffices);
    }


    /**
     * Download Tar file from Crunchbase
     *
     * @param bool $useCachedDownload
     * @return string
     * @throws Exception
     */
    private function downloadData($useCachedDownload = false)
    {
        // Download csv_export
        $this->line(' Downloading data from crunchbase...');
        $tarFile = public_path((config('crunchbase.files_path') . 'csv_export.tar.gz'));

        if (!File::exists(public_path((config('crunchbase.files_path'))))) {
            File::makeDirectory(public_path((config('crunchbase.files_path'))));
        }

        if ($useCachedDownload && File::exists($tarFile)) {
            $this->line(' Using cached download');
            return $tarFile;
        }

        $api_key   = config('crunchbase.user_key');
        if (empty($api_key)) {
            print_r("Please make sure you have the variable CRUNCHBASE_KEY on your /.env file setted\n\r");
            print_r("Then run the followin commands:\n\r");
            print_r("php artisan config:clear\n\r");
            print_r("php artisan cache:clear\n\r");
            die;
        }
        $url = $this->api_url . "/csv_export/csv_export.tar.gz?user_key=" . $api_key;

        $downloadProcess = new Process("curl  -o $tarFile $url ", null, null, null, null);
        $output = $this->output;
        $downloadProcess->run(function ($type, $buffer) use ($output) {
            $this->output->write($buffer);
        });

        if (! $downloadProcess->isSuccessful()) {
            throw new Exception('Couldn\'t download tar file from Crunchbase');
        }

        return $tarFile;
    }


    /**
     * Extract a file from Tar zip, and run a clean function to fix if csv is broken
     *
     * @param $tarFile
     * @param $file
     * @param bool $useCachedCsv
     * @return string
     * @throws Exception
     */
    private function extractAndCleanCsv($tarFile, $file, $useCachedCsv = false)
    {
        $basePath = public_path(config('crunchbase.files_path') . "csv");

        $sourceFilename = "$basePath/$file.csv";
        $tempFilename = "$basePath/{$file}_temp.csv";
        $url = config('app.url') . '/' . config('crunchbase.files_path') . "csv/$file.csv";

        if ($useCachedCsv && File::exists($sourceFilename)) {
            $this->line(" Using cached csv for $file");
            return $url;
        }

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath);
        }

        $this->line(" Extracting $file CSV from tar...");
        $process = new Process("tar -xvzf $tarFile -C $basePath $file.csv");
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("Couldn't extract $file.csv from $tarFile");
        }

        $this->cleanCsv($sourceFilename, $tempFilename);

        return $url;
    }


    /**
     * Clear CSV file
     *
     * @param $sourceFilename
     * @param $tempFilename
     */
    private function cleanCsv($sourceFilename, $tempFilename)
    {
        $this->line(' Cleaning CSV file...');

        // copy operation
        $sourceFile = fopen($sourceFilename, 'r');
        $tempFile = fopen($tempFilename, 'w');

        $headings = str_getcsv(fgets($sourceFile));
        $headingsCount = count($headings);
        fputcsv($tempFile, $headings);

        $this->line(" Columns found: $headingsCount");

        $this->fixCsvLines($sourceFile, $tempFile, $headingsCount);

        $this->line('');
        fclose($sourceFile);
        fclose($tempFile);
        // delete old source file
        unlink($sourceFilename);
        // rename target file to source file
        $this->line(' Cleaning temporary file...');
        rename($tempFilename, $sourceFilename);
    }


    /**
     * Clear CSV String line
     *
     * @param $sourceFile
     * @param $tempFile
     * @param $headingsCount
     */
    private function fixCsvLines($sourceFile, $tempFile, $headingsCount)
    {
        $errors = [];
        $csvLineNumber = 1;
        $bar = $this->output->createProgressBar();
        $bar->setRedrawFrequency(1000);
        while (!feof($sourceFile)) {
            $csvLineNumber++;
            $line = fgets($sourceFile);
            $csv_line = str_getcsv($line);

            if (count($csv_line) != $headingsCount) {
                $line = str_replace('\","', '","', $line);
                $csv_line = str_getcsv($line);

                $subLineCount = 0;

                while (count($csv_line) < $headingsCount && !feof($sourceFile)) {
                    $line .= str_replace("\r", "", fgets($sourceFile));
                    $csv_line = str_getcsv($line);
                    $subLineCount++;
                }
                if (count($csv_line) != $headingsCount) {
                    $errors[] = " Invalid csv line on line $csvLineNumber\r\n$line";
                    continue;
                }
                $csvLineNumber += $subLineCount;
            }
            fputcsv($tempFile, $csv_line);
            $bar->advance();
        }
        $bar->finish();
        $this->line('');
        foreach ($errors as $error) {
            $this->warn($error);
        }
    }


    /**
     * Analize CSV File for Company and Investor updates
     *
     * @param $csvUrl
     * @param bool $ignoreOffices
     */
    private function analyzeData($csvUrl, $ignoreOffices = false)
    {
        $entities = [
            'Company'   => Company::class,
            'Investor'  => Investor::class,
        ];

        $stats = [];
        foreach ($entities as $entityName => $entityClass) {
            $this->line(" Retrieving existing $entityName items from graph...");
            $results =  $this->getEntityData($csvUrl, $entityName);
            $rows_count = 0;
            $officesAdded = 0;
            $totalRows = count($results);

            $this->line(' Parsing downloaded data...');
            $bar = $this->output->createProgressBar($totalRows);
            foreach ($results as $result) {
                $row = $result[1];

                if ($result[0]->crunchbase_updated_at != $row["updated_at"] || $this->force_update) {
                    $entity = $entityClass::find($result[0]->getId());
                    $this->updateRecord($entity, $row);

                    if (!$ignoreOffices) {
                        $officesAdded += $this->saveOffices(
                            $row["cb_url"],
							strtolower($entityName),
                            [Office::class,  strtolower($entityName) . 'LocatedAtOffice'],
                            $entity->impactspace_id
                        );
                    }
                }
                $rows_count++;
                $bar->advance();
            }
            $bar->finish();
            $this->line('');

            $stats[$entityName] = [
                'entity'          => $entityName,
                'records with Crunchbase data' => $this->getEntityUpdateStats($entityName),
                'offices added'   => $officesAdded,
            ];
        }

        $this->table(['entity','records with Crunchbase data','offices added'], $stats);
    }


    /**
     * Return Entity Query String to match existing data with Crunchbase information
     * @param $csvUrl
     * @param $entityName
     * @return object
     */
    private function getEntityData($csvUrl, $entityName)
    {
        $queryString = "
                LOAD CSV WITH HEADERS FROM {file_url} AS row
                MATCH (e:$entityName)
                WHERE row.company_name IS NOT NULL 
                    AND row.company_name <> \"\" 
                    AND row.company_name = e.name 
                    AND row.roles =~ {role}
                RETURN e, row
                UNION 
                LOAD CSV WITH HEADERS FROM {file_url} AS row 
                MATCH (e:$entityName)
                WHERE row.homepage_url IS NOT NULL 
                    AND row.homepage_url <> \"\" 
                    AND row.homepage_url = e.website 
                    AND row.roles =~ {role}
                RETURN e, row
            ";
        $queryParams = [
            'file_url' => $csvUrl,
            'role' => '.*' . strtolower($entityName) . '.*',
        ];

        return  $this->executeQuery($queryString, $queryParams);
    }

    /**
     * Update entity record with crunhbase data
     *
     * @param $entity
     * @param $data
     * @return int
     */
    private function updateRecord(&$entity, $data)
    {
        $entity->crunchbase_id = $data["uuid"];
        $entity->crunchbase_url = $data["cb_url"];
        $entity->crunchbase_updated_at = $data["updated_at"];

        $mappings = [
            'email' => 'email',
            'facebook_url' => 'facebook',
            'twitter_url' => 'twitter',
            'linkedin_url' => 'linkedin',
            'phone' => 'phone',
            'founded_on' => 'founded_date',
            'profile_image_url' => [
                'callable' => [$this, 'getProfilePic'],
                'map' => 'company_logo',
            ]
        ];
        $fieldsImported = [];
        foreach ($mappings as $source => $map) {
            if (is_array($map)) {
                $callable = $map['callable'];
                $property = $map['map'];
            } else {
                $callable = false;
                $property = $map;
            }

            if ((!isset($entity->$property) || empty($entity->$property)) && !empty($data[$source])) {
                $entity->$property = (false === $callable) ?
                    $data[$source] : call_user_func($callable, $data[$source]);
                $fieldsImported[] = $source;
            }
        }
        $touchedFieldsCount = count($fieldsImported);

        $entity->crunchbase_fields = (empty($entity->crunchbase_fields) ?
                '' :
                $entity->crunchbase_fields . ',') . join(',', $fieldsImported)
        ;
        $entity->crunchbase_fields_count = (empty($entity->crunchbase_fields_count) ?
                0 :
                $entity->crunchbase_fields_count) + $touchedFieldsCount
        ;

        if ($touchedFieldsCount) {
            $entity->save();
        }
        return $entity->crunchbase_fields_count;
    }

    /**
     * Get offices from a specific entity, and save them if they not exist
     *
     * @param $crunchbaseCbUrl
     * @param $saveMethod
     * @param $referencedEntityId
     * @return int
     */
    private function saveOffices($crunchbaseCbUrl, $entity, $saveMethod, $referencedEntityId)
    {
        $permalink = str_replace("https://www.crunchbase.com/organization/", "", $crunchbaseCbUrl);
        $service_url = $this->api_url .'/organizations/'.$permalink.'?user_key=' . config('crunchbase.user_key');
        $data = self::getApiData($service_url);

        $officesAdded = 0;
        if (null != $data) {
            $offices = $this->getOfficesData($data);
            $headquarters = $this->getHeadquartersData($data);

            foreach ($offices as $office) {
                $office_properties = $office->properties;
                $address = $office_properties->street_1 . ' ' . $office_properties->street_2;
                $existing_office = Office::where('address', '=', $address)
                    ->where('city', '=', $office_properties->city)
                    ->where('postal_code', '=', $office_properties->postal_code)
                    ->first();
                $geo_area_id = 0;

                if (!$existing_office) {
                    $officesAdded++;
                    $existing_office = $this->addNewOffice($office->uuid, $address, $headquarters, $office_properties);

                    if (!empty($office_properties->country)) {
                        $geo_area_id = $this->getGeoAreaId(
                            $office_properties->country,
                            $office->uuid,
                            $office_properties,
                            $address
                        );
                    }
                }
                if ($geo_area_id) {
					$args = [
						'office_id' => $existing_office->id,
						'geographic_id' => $geo_area_id,
					];
					if("company"==$entity){
						$args['company_id'] = $referencedEntityId;
					}elseif("investor"==$entity){
						$args['investor_id'] = $referencedEntityId;
					}

                    call_user_func($saveMethod, $args);
                }
            }
        }
        return $officesAdded;
    }


    /**
     * Extract Office Information
     *
     * @param $data
     * @return array
     */
    private function getOfficesData($data)
    {
        $offices = array();
        if ($data->relationships->offices->paging->total_items >= 1) {
            $offices[] = $data->relationships->offices->item;
        }
        return $offices;
    }


    /**
     * Extract Headquarters Information
     *
     * @param $data
     * @return array
     */
    private function getHeadquartersData($data)
    {
        $headquarters = array();
        if ($data->relationships->headquarters->paging->total_items >= 1) {
            $headquarters[] = $data->relationships->headquarters->item;
        }
        return $headquarters;
    }


    /**
     * Create new Office
     *
     * @param $office_id
     * @param $address
     * @param $headquarters
     * @param $office_properties
     * @return \App\Office
     */
    private function addNewOffice($office_id, $address, $headquarters, $office_properties)
    {
        $o_params = [];

        $entity_data = $this->groupEntityData('office', 'top_level', $office_id, $office_id);
        foreach ($headquarters as $headquarter) {
            if ($office_id == $headquarter->uuid) {
                $o_params = $this->setIndexToArray($o_params, 'headquarter', 1, $entity_data);
                continue;
            }
        }
        $o_params = $this->setIndexToArray($o_params, 'crunchbase_id', $office_id, $entity_data);
        $o_params = $this->setIndexToArray($o_params, 'address', $address, $entity_data);
        $o_params = $this->setIndexToArray($o_params, 'city', $office_properties->city, $entity_data);
        $o_params = $this->setIndexToArray($o_params, 'state', $office_properties->region, $entity_data);
        $o_params = $this->setIndexToArray($o_params, 'postal_code', $office_properties->postal_code, $entity_data);

        $new_office = Office::create($o_params);
        return $new_office;
    }


    /**
     * Return Geographic Area ID from a specific Country
     * @param $country
     * @param $office_id
     * @param $office_properties
     * @param $address
     * @return int
     */
    private function getGeoAreaId($country, $office_id, $office_properties, $address)
    {
        $geo_area_id = 0;
        $mapped_country = $country;

        foreach ($this->geographic_areas_mapping as $mapped => $value) {
            if (in_array($office_properties->country, $value)) {
                $mapped_country = $mapped;
                $this->createInvalidData(
                    'office',
                    'country',
                    $office_properties->country,
                    'foo_country',
                    $office_properties->country,
                    $office_id
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
                $address,
                'foo_country',
                $mapped_country
            );
        }
        return $geo_area_id;
    }


    /**
     * Return count of entities updated
     *
     * @param $entityName
     * @return int
     */
    private function getEntityUpdateStats($entityName)
    {
        $queryString = "
                MATCH (c:$entityName)
                WHERE c.crunchbase_fields_count IS NOT NULL AND c.crunchbase_fields_count > 0
                RETURN count(c)
            ";
        $results = $this->executeQuery($queryString);
        return $results[0]['raw'];
    }


    /**
     * Download an image and return the new image url location
     *
     * @param $url
     * @return string
     */
    private function getProfilePic($url)
    {
        if (empty($url)) {
            return "";
        }
        try {
            if (!File::exists(public_path("uploads/" . config("crunchbase.profile_path")))) {
                File::makeDirectory(public_path("uploads/" . config("crunchbase.profile_path")));
            }

            $filename = basename($url);
            $file_path = public_path("uploads/" . config("crunchbase.profile_path") . $filename);

            file_put_contents($file_path, fopen($url, 'r'));

            $image_url = config('app.url') . "/uploads/" . config("crunchbase.profile_path") . $filename;
        } catch (\Exception $e) {
            return "";
        }
        return $image_url;
    }
}
