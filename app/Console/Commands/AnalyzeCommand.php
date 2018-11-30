<?php namespace App\Console\Commands;

use App\AnalyzeHandlers\DataLog;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use App\AnalyzeHandlers\InvalidData;

abstract class AnalyzeCommand extends Command
{

    protected $force_update = false;

    protected $signature = '';

    protected $name = '';

    protected $datasource = '';

    protected $description = '';

    protected $client = null;

    public $geographic_areas_mapping = [
        "United States" => [
            "United States of America",
            "US"
        ],
        "United Kingdom" => [
            "United Kingdom of Great Britai",
            "United Kingdom of Great Britain and Northern Irela"
        ],
        "Nigeria" => [
            "NG"
        ],
        "Switzerland" => [
            "CH"
        ],
        "Mexico" => [
            "MX"
        ],
        "Hong Kong" => [
            "HK"
        ],
        "Panama" => [
            "PA"
        ],
        "Bolivia" => [
            "BO"
        ],
        "Bangladesh" => [
            "BD"
        ],
        "Hong Kong" => [
            "China, Hong Kong Special Admin"
        ],
        "North Korea" => [
            "Democratic People's Republic o"
        ],
        "India" => [
            "IN"
        ],
        "Bolivia" => [
            "Bolivia (Plurinational State o"
        ],
        "Austria" => [
            "AT"
        ],
        "Moldova" => [
            "Republic of Moldova"
        ],
        "Tanzania" => [
            "United Republic of Tanzania"
        ],
        "Germany" => [
            "DE"
        ],
        "South Africa" => [
            "ZA"
        ],
        "Kenya" => [
            "KE"
        ],
        "Cote D'ivoire" => [
            "Cote D'Ivoire"
        ],
        "Belgium" => [
            "BE"
        ],
        "France" => [
            "FR"
        ],
        "Macao" => [
            "China, Macao Special Administr"
        ],
        "Palestinia" => [
            "Palestinian Territory, Occupie"
        ],
        "North America" => [
            "Northern America"
        ]
    ];

    /**
     *  Run command analyze update
     */
    public function handle()
    {
        $time_start = microtime(true);

        $this->info('Starting ' . $this->datasource . ' import...');

        $this->force_update = !empty($this->option('force_update'));

        $this->handleImport();

        $time_end = microtime(true);
        $time_diff = $time_end - $time_start;

        $this->info("Import finished, total processing time: " . $this->timeFormat($time_diff));

        DataLog::create([
            'datasource' => $this->datasource,
            'start_time' => date('Y-m-d H:i:s', $time_start),
            'end_time' => date('Y-m-d H:i:s', $time_end),
        ]);
    }

    /**
     * Handle import abstract function
     *
     * @return mixed
     */
    abstract protected function handleImport();

    /**
     * Format time in H:m:s
     *
     * @param int $duration
     * @return string
     */
    protected function timeFormat($duration)
    {
        $hours = (int)($duration / 60 / 60);
        $minutes = (int)($duration / 60) - $hours * 60;
        $seconds = (int)$duration - $hours * 60 * 60 - $minutes * 60;

        $hours_str = ($hours == 0)? "00" : ($hours < 10 ? "0" . $hours : $hours);
        $minutes_str = ($minutes == 0)? "00" : ($minutes < 10 ? "0" . $minutes : $minutes);
        $seconds_str = ($seconds == 0)? "00" : ($seconds < 10 ? "0" . $seconds : $seconds);
        return $hours_str . ":" . $minutes_str . ":" . $seconds_str;
    }

    /**
     * Set indext to array and filter data before set
     *
     * @param string $entity
     * @param string $type
     * @param string $title
     * @param string datasource_id
     * @param string datasource_timestamp
     * @return array
     */
    public function groupEntityData(
        $entity,
        $type,
        $title,
        $datasource_id = "",
        $datasource_timestamp = ""
    ) {
        $entity_data = array();
        $entity_data["entity"] = $entity;
        $entity_data["type"] = $type;
        $entity_data["title"] = $title;
        $entity_data["datasource_id"] = $datasource_id;
        $entity_data["datasource_timestamp"] = $datasource_timestamp;

        return $entity_data;
    }
    /**
     * Set indext to array and filter data before set
     *
     * @param array $params
     * @param string $index
     * @param string $value
     * @return array
     */
    public function setIndexToArray(
        $array,
        $index,
        $value,
        $entity_data
    ) {
        if (!empty($value) &&
            $value != "@" &&
            $value != "http://"
        ) {
            $array[$index] = $value;
        } else {
            $this->createInvalidData(
                $entity_data["entity"],
                $entity_data["type"],
                $entity_data["title"],
                $index,
                $value,
                $entity_data["datasource_id"],
                $entity_data["datasource_timestamp"]
            );
        }
        return $array;
    }

    /**
     * Set index to object and filter data before set
     *
     * @param object  $obj
     * @param string  $index
     * @param string  $value
     * @return void
     */
    public function setIndexToObject(
        $obj,
        $index,
        $value,
        $entity,
        $type,
        $title,
        $datasource_id = "",
        $datasource_timestamp = ""
    ) {
        if (!empty($value) &&
            $value!="@"
        ) {
            $obj->$index = $value;
        } else {
            $this->createInvalidData(
                $entity,
                $type,
                $title,
                $index,
                $value,
                $datasource_id,
                $datasource_timestamp
            );
        }
    }

    /**
     * Save Invalid data
     *
     * @param $entity
     * @param $type
     * @param $title
     * @param $field_name
     * @param $value
     * @param string $datasource_id
     * @param string $datasource_timestamp
     */
    public function createInvalidData(
        $entity,
        $type,
        $title,
        $field_name,
        $value,
        $datasource_id = "",
        $datasource_timestamp = ""
    ) {
        $invalid_data = InvalidData::where([
            ['entity_type', '=', $entity],
            ['title', '=', utf8_encode($title)],
            ['datasource', '=', $this->datasource],
            ['datasource_id', '=', $datasource_id],
            ['update_timestamp', '=', $datasource_timestamp]
        ])->first();

        if (!$invalid_data) {
            try {
                InvalidData::create([
                    'entity_type' => $entity,
                    'entity_category' => $type,
                    'title' => utf8_encode($title),
                    'field' => $field_name,
                    'value' => utf8_encode($value),
                    'datasource' => $this->datasource,
                    'datasource_id' => $datasource_id,
                    'update_timestamp' => $datasource_timestamp,
                ]);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Return arguments of the call from console to handle many type of calls to the api
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['type', InputArgument::OPTIONAL,
                'type of data to analyze'],
        ];
    }

    /**
     * Return result of query execution
     *
     * @param string $query_str
     * @param array  $params
     * @return object
     */
    public function executeQuery($query_str, $params = array())
    {
        $this->client = \DB::connection('neo4j')->getClient();
        $query = new Query($this->client, $query_str, $params);
        return $query->getResultSet();
    }

    /**
     * Return array with specific number of blank spaces
     *
     * @return array
     */
    public function fillEmptySpace($length)
    {
        $empty_array = [];
        for ($i = 0; $i < $length; $i++) {
            $empty_array[] = "";
        }
        return $empty_array;
    }

    /**
     * Return string with specific number of repeated string
     *
     * @return string
     */
    public function repeatString($key, $times)
    {
        $return_str = "";
        for ($i = 0; $i < $times; $i++) {
            $return_str .= $key;
        }
        return $return_str;
    }

    /**
     * Function used to remove BOM character
     * @param $data
     * @return string
     */
    protected function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        } else {
            return $data;
        }
    }

    /**
     * Function used to replace quotes by "{}{}" string
     * NOTE: this method is intended to call it with a "preg_replace_callback"
     *
     * @param string $str
     * @return string
     */
    protected function replaceQuotes($str)
    {
        return "{}{}".substr($str[0], 1, strlen($str[0])-3)."{}{}:";
    }

    /**
     * Function used to scape urls when replace by regex
     * NOTE: this method is intended to call it with a "preg_replace_callback"
     *
     * @param string $str
     * @return string
     */
    protected function scapeUrls($str)
    {
        return json_encode($str[0]);
    }

    /**
     * Call api Data with Curl
     *
     * @param $url
     * @return object
     */
    protected function getApiData($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
            die('error occured during curl exec. Additioanl info: ' . var_export($info));
        }
        $decoded = json_decode($curl_response);
        if (isset($decoded->error)) {
            return null;
        }
        if (isset($decoded->data)) {
            return $decoded->data;
        } else {
            return null;
        }
    }
}
