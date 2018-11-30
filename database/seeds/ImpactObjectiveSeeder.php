<?php

use Illuminate\Database\Seeder;
use Everyman\Neo4j\Cypher\Query;

class ImpactObjectiveSeeder extends Seeder
{
    private $impactObjectives = [
        'social' => [
            'Access to Clean Water and Sanitation',
            'Access to Education',
            'Access to Energy',
            'Access to Financial Services',
            'Access to Information',
            'Affordable Housing',
            'Agricultural Productivity',
            'Capacity Building',
            'Community Development',
            'Conflict Resolution',
            'Disease Specific Prevention and Mitigation',
            'Employment Generation',
            'Equality and Empowerment/Minorities/Previously Excluded Populations',
            'Food Security',
            'Generate Funds for Charitable Giving',
            'Health Improvement',
            'Human Rights Protection or Expansion',
            'Income/Productivity Growth',
            'Women and Girls',
            'Low Income Country',
            'Other',
        ],
        'environmental' => [
            'Biodiversity Conservation',
            'Energy and Fuel Efficiency',
            'Natural Resources Conservation',
            'Pollution Prevention & Waste Management',
            'Sustainable Energy',
            'Sustainable Land Use',
            'Water Resources Management',
            'Other',
        ],
        'operational' => [
            'Environmental Policies and Performance',
            'Governance and Ownership',
            'Social Policies and Performance',
            'Other',
        ]
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $client = \DB::connection('neo4j')->getClient();
        $currentDate = date('Y-m-d H:i:s');

        foreach ($this->impactObjectives as $type => $objectives) {
            foreach ($objectives as $objective) {
                $query = new Query(
                    $client,
                    '
                    MERGE (i:ImpactObjective { name : {name}, type: {type}, slug: {slug} })
                    ON CREATE SET i.created_at = {currentDate}, i.updated_at = {currentDate}
                    ON MATCH SET i.updated_at = {currentDate}
                ',
                    [
                        'slug' => str_slug('Other' === $objective ? "$type Other" : $objective),
                        'name' => $objective,
                        'type' => $type,
                        'currentDate' => $currentDate,
                    ]
                );
                $query->getResultSet();
            }
        }
    }
}
