<?php

namespace DongttFd\LaravelUploadModel\Test;

use DongttFd\LaravelUploadModel\Test\Models\JsonArrayFileModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadJsonArrayFileTest extends TestCase
{
    /** @var string */
    protected $modelName = JsonArrayFileModel::class;

    /** @var Dongtt\LaravelUploadModel\Eloquent\FileModel */
    protected $fileModelInstance;

    /** @test */
    public function testCreateJsonArrayFileModel()
    {
        $this->createFileModel();
    }

    /** @test */
    public function testUpdateJsonArrayFileModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $file3 = UploadedFile::fake()->image(Str::random(10) . '.png');
            $fileModelInstance
                ->fill([
                    'path' => [
                        $file,
                        $file3,
                    ],
                ])
                ->save();

            $this->assertNotTrue(Storage::disk('public')->exists($file2->hashName()));
            $this->assertArrayContains1D([
                $file->hashName(),
                $file3->hashName(),
            ], $fileModelInstance->path);

            $this->assertArrayContains1D([
                config('filesystems.disks.public.url') . '/' . $file->hashName(),
                config('filesystems.disks.public.url') . '/' . $file3->hashName(),
            ], $fileModelInstance->path_url);
        });
    }

    /** @test */
    public function testDeleteJsonArrayFileModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $fileModelInstance->delete();
            $this->assertNotTrue(Storage::disk('public')->exists($file->hashName()));
            $this->assertNotTrue(Storage::disk('public')->exists($file2->hashName()));
        });
    }

    /** @test */
    public function testToArrayJsonArrayFileModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $this->assertArrayContains1D([
                'path' => [
                    $file->hashName(),
                    $file2->hashName(),
                ],
                'path_url' => [
                    config('filesystems.disks.public.url') . '/' . $file->hashName(),
                    config('filesystems.disks.public.url') . '/' . $file2->hashName(),
                ],
            ], $fileModelInstance->toArray());
        });
    }

    private function createFileModel($callback = null)
    {
        $this->assertTrue($this->model instanceof JsonArrayFileModel);

        $file = UploadedFile::fake()->image(Str::random(10) . '.png');
        $file2 = UploadedFile::fake()->image(Str::random(10) . '.png');

        $fileModelInstance = $this->model->create([
            'path' => [
                $file,
                $file2,
            ],
        ]);

        $this->assertArrayContains1D([
            $file->hashName(),
            $file2->hashName(),
        ], $fileModelInstance->path);

        $this->assertArrayContains1D([
            config('filesystems.disks.public.url') . '/' . $file->hashName(),
            config('filesystems.disks.public.url') . '/' . $file2->hashName(),
        ], $fileModelInstance->path_url);

        if ($callback) {
            $callback($fileModelInstance, $file, $file2);
        }
    }
}
