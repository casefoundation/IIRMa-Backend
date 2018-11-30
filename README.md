# Networkmap
This project pretend to provide a centralized data of companies and investors, gathered from various data sources and present it as graph for consulting.

## Getting started
This guide pretend to explain how to deploy the api server on a standard hardware.

## Prerequisites
Before start there are some software to install on the server in order to deploy the project:
* Web server
* php 7.1
* laravel 5.5
* neo4j
* MySQL

## Installing
1. Clone the project from github to your destination:
   ```git
   git clone git@github.com:CreativeScienceLabs/cf-network-map-backend.git .
   ```
1. Run composer on root project directory in order to install dependencies
   ```
   composer install
   ```
1. Create `.env` file based on `.env.example` on root project directory and set hosts, ports, username, passwords and
   database for mysql, neo4j and app variables. Also add impactspace and crunchbase api keys provided by the them.
1. Point your web server to `/public` folder inside root project directory in order to access via web as
   standard Laravel configuration.
1. For neo4j is recommendable to close default ports for access from outside or via web.

## Deployment
1. Run migrations and seed
   ```
   php artisan migrate
   php artisan migrate --seed
   ```
   NOTE: There are a **default admin** user added on seed, you have to update the password to avoid potentially security threats. Also was added a basic api user to use the api externally.
1. Run datasource analyzers (may take several time depends on cpu power)
   ```
   php artisan impactspace:analyze
   php artisan crunchbase:analyze
   ```
1. Make sure to add Laravel scheduler to cron in order to automatically run the datasource analyzers.
