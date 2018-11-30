<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');*/

$api = app('Dingo\Api\Routing\Router');
$api->version(['v1'], ['middleware' => 'api.auth'], function ($api) {
    $api->get('/companies', [
        'as' => 'companies.all',
        'uses' => 'App\Api\V1\Controllers\CompanyController@index'
    ]);
    $api->get('/company/{company_id}', [
        'as' => 'companies.show',
        'uses' => 'App\Api\V1\Controllers\CompanyController@show'
    ])-> where(['company_id'=>'[0-9]*']);
    $api->get('/company_attributes', [
        'as' => 'company.attributes',
        'uses' => 'App\Api\V1\Controllers\CompanyController@filterAttributes'
    ]);
    $api->get('/companies/top_industries/{top_number}', [
        'as' => 'investors.top_industries',
        'uses' => 'App\Api\V1\Controllers\CompanyController@topByIndustries'
    ])-> where(['top_number'=>'[0-9]*']);
    $api->get('/companies/top_funds_type/{top_number}', [
        'as' => 'investors.top_industries', 'uses' => 'App\Api\V1\Controllers\CompanyController@topFundsType'
    ])-> where(['top_number'=>'[0-9]*']);
    $api->get('/companies/legal_structures', [
        'as' => 'companies.legal_structures', 'uses' => 'App\Api\V1\Controllers\CompanyController@legalStructures'
    ]);

    $api->get('/investors', [
        'as' => 'investors.all',
        'uses' => 'App\Api\V1\Controllers\InvestorController@index'
    ]);
    $api->get('/investor/{investor_id}', [
        'as' => 'investors.show',
        'uses' => 'App\Api\V1\Controllers\InvestorController@show'
    ])-> where(['investor_id'=>'[0-9]*']);
    $api->get('/investor_attributes', [
        'as' => 'investors.attributes',
        'uses' => 'App\Api\V1\Controllers\InvestorController@filterAttributes'
    ]);
    $api->get('/investors/top/{top_number}', [
        'as' => 'investors.top',
        'uses' => 'App\Api\V1\Controllers\InvestorController@top'
    ])-> where(['top_number'=>'[0-9]*']);
    $api->get('/investors/top_companies/{top_number}', [
        'as' => 'investors.top_companies',
        'uses' => 'App\Api\V1\Controllers\InvestorController@topCompanies'
    ])-> where(['top_number'=>'[0-9]*']);
    $api->get('/investors/legal_structures', [
        'as' => 'investors.legal_structures',
        'uses' => 'App\Api\V1\Controllers\InvestorController@legalStructures'
    ]);

    $api->get('/geographic/top_funded/{top_number}', [
        'as' => 'geographic.top_funded',
        'uses' => 'App\Api\V1\Controllers\GeographicAreaController@topFunded'
    ])-> where(['top_number'=>'[0-9]*']);

    $api->get('/global/total_funds', [
        'as' => 'global.total_funds',
        'uses' => 'App\Api\V1\Controllers\GlobalController@totalFunds'
    ])-> where(['top_number'=>'[0-9]*']);
    $api->get('/global/total_companies_with_funds', [
        'as' => 'global.total_funds',
        'uses' => 'App\Api\V1\Controllers\GlobalController@totalCompaniesWithFunds'
    ])-> where(['top_number'=>'[0-9]*']);
    $api->get('/global/impact_objectives', [
        'as' => 'global.impact_objectives',
        'uses' => 'App\Api\V1\Controllers\GlobalController@impactObjectives'
    ]);
    $api->get('/global/geographic_areas', [
        'as' => 'global.geographic_areas',
        'uses' => 'App\Api\V1\Controllers\GlobalController@geographicAreas'
    ]);
    $api->get('/global/global_data', [
        'as' => 'global.global_data',
        'uses' => 'App\Api\V1\Controllers\GlobalController@globalData'
    ]);
    $api->get('/global/global_date_data', [
        'as' => 'global.global_date_data',
        'uses' => 'App\Api\V1\Controllers\GlobalController@globalDateData'

    ]);
});

$api->version(['v1'], function ($api) {
    $api->post('/authenticate', [
        'as' => 'authenticate.all',
        'uses' => 'App\Api\V1\Controllers\AuthenticateController@index'
    ]);
    $api->get('/global/all_data', [
        'as' => 'global.global_data',
        'uses' => 'App\Api\V1\Controllers\GlobalController@allData'
    ]);
});
