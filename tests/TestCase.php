<?php

namespace AntonioPrimera\ApiClient\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase
{
	//use RefreshDatabase;

	/**
	 * Override this and provide a list of:
	 * [ 'path/to/migration/file1.php.stub' => 'MigrationClass1', ... ]
	 *
	 * @var array
	 */
	protected $migrate = [
		//__DIR__ . '/TestContext/migrations/create_users_table.php.stub' => 'CreateUsersTable',
		//__DIR__ . '/../database/migrations/add_role_to_users_table.php.stub' => 'AddRoleToUsersTable',
	];
	
	protected function setUp(): void
	{
		parent::setUp();
	}
	
	protected function getPackageProviders($app)
	{
		return [
			//\AntonioPrimera\ApiClient\LaravelApiClientServiceProvider::class,
		];
	}

	protected function getEnvironmentSetUp($app)
	{
		if ($this->migrate)
			$this->runPackageMigrations();
	}

	//--- Protected helpers -------------------------------------------------------------------------------------------

	protected function runPackageMigrations()
	{
		//this will reset the database
		Artisan::call('migrate:fresh');

		//import all migration files
		foreach ($this->migrate as $migrationFile => $migrationClass) {
			include_once $migrationFile;
			(new $migrationClass)->up();
		}
	}
}