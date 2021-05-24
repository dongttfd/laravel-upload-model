<?php

namespace DongttFd\LaravelUploadModel\Test;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @var Model */
    protected $model;

    /** @var string */
    protected $modelName;

    public function setUp(): void
    {
        parent::setUp();

        $this->createDatabase($this->app);
        $this->makeModel();
    }

    /**
     * @return Model
     * @throws RepositoryException
     */
    public function makeModel()
    {
        $model = $this->app->make($this->modelName);

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->modelName} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Setup database
     *
     * @param Illuminate\Foundation\Application $app
     * @return void
     */
    private function createDatabase(Application $app)
    {
        $app['db']->connection()
            ->getSchemaBuilder()
            ->create('files', function (Blueprint $table) {
                $table->increments('id');
                $table->string('path')->nullable();
            });
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:'
        ]);

        $app['config']->set('filesystems.default', 'public');
        $app['config']->set('filesystems.disks', [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app')
            ],
            'public' => [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => 'http://localhost/storage',
                'visibility' => 'public'
            ],
            's3' => [
                'driver' => 's3',
                'key' => 'AWS_ACCESS_KEY_ID',
                'secret' => 'AWS_SECRET_ACCESS_KEY',
                'region' => 'AWS_DEFAULT_REGION',
                'bucket' => 'AWS_BUCKET',
                'url' => 'AWS_URL',
                'endpoint' => 'AWS_ENDPOINT',
                // 'key' => env('AWS_ACCESS_KEY_ID'),
                // 'secret' => env('AWS_SECRET_ACCESS_KEY'),
                // 'region' => env('AWS_DEFAULT_REGION'),
                // 'bucket' => env('AWS_BUCKET'),
                // 'url' => env('AWS_URL'),
                // 'endpoint' => env('AWS_ENDPOINT')
            ]
        ]);
    }

    /** @test */
    public function testInit()
    {
        $this->assertTrue(true, 'Init Testing');
    }
}
