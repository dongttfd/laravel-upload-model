<?php

namespace DongttFd\LaravelUploadModel\Test;

use DongttFd\LaravelUploadModel\Exceptions\UploadEloquentException;
use DongttFd\LaravelUploadModel\Test\Models\JsonFileModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadJsonFileTest extends TestCase
{
    /** @var string */
    protected $modelName = JsonFileModel::class;

    /** @var Dongtt\LaravelUploadModel\Eloquent\FileModel */
    protected $fileModelInstance;

    /** @test */
    public function testExceptionsFileFields()
    {
        $this->assertTrue($this->model instanceof JsonFileModel);
        $this->model->configFileFields([1]);
        $this->expectExceptionMessage('Element in ' . JsonFileModel::class . '::$fileFields must is String');
        $this->expectException(UploadEloquentException::class);
        $this->model->fill(['path' => [
            'first' => UploadedFile::fake()->image(Str::random(10) . '.png'),
        ]]);
    }

    /** @test */
    public function testCreateJsonFileFieldModel()
    {
        $this->createFileModel();
    }

    /** @test */
    public function testUpdateJsonFileFieldModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $file3 = UploadedFile::fake()->image(Str::random(10) . '.png');
            $fileModelInstance
                ->fill([
                    'path' => [
                        'other' => 'bcsok',
                        'first' => $file->hashName(),
                        'second' => $file3,
                    ],
                ])
                ->save();

            $this->assertNotTrue(Storage::disk('public')->exists($file2->hashName()));
            $this->assertEquals($fileModelInstance->path, [
                'other' => 'bcsok',
                'first' => $file->hashName(),
                'second' => $file3->hashName(),
                'first_url' => config('filesystems.disks.public.url') . '/' . $file->hashName(),
                'second_url' => config('filesystems.disks.public.url') . '/' . $file3->hashName(),
            ]);
        });
    }

    /** @test */
    public function testDeleteJsonFileFieldModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $fileModelInstance->delete();
            $this->assertNotTrue(Storage::disk('public')->exists($file->hashName()));
            $this->assertNotTrue(Storage::disk('public')->exists($file2->hashName()));
        });
    }

    /** @test */
    public function testToArrayJsonFileFieldModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $this->assertArrayContains1D([
                'path' => [
                    'other' => 'bcsok',
                    'first' => $file->hashName(),
                    'second' => $file2->hashName(),
                    'first_url' => config('filesystems.disks.public.url') . '/' . $file->hashName(),
                    'second_url' => config('filesystems.disks.public.url') . '/' . $file2->hashName(),
                ],
            ], $fileModelInstance->toArray());
        });
    }

    private function createFileModel($callback = null)
    {
        $this->assertTrue($this->model instanceof JsonFileModel);
        $file = UploadedFile::fake()->image(Str::random(10) . '.png');
        $file2 = UploadedFile::fake()->image(Str::random(10) . '.png');

        $fileModelInstance = $this->model->create([
            'path' => [
                'other' => 'bcsok',
                'first' => $file,
                'second' => $file2,
            ],
        ]);

        $this->assertArrayContains1D([
            'other' => 'bcsok',
            'first' => $file->hashName(),
            'second' => $file2->hashName(),
            'first_url' => config('filesystems.disks.public.url') . '/' . $file->hashName(),
            'second_url' => config('filesystems.disks.public.url') . '/' . $file2->hashName(),
        ], $fileModelInstance->path);

        if ($callback) {
            $callback($fileModelInstance, $file, $file2);
        }
    }
}
