<?php

namespace DongttFd\LaravelUploadModel\Test;

use DongttFd\LaravelUploadModel\Test\Models\JsonArrayFileOnFieldModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadJsonArrayFileOnFieldTest extends TestCase
{
    /** @var string */
    protected $modelName = JsonArrayFileOnFieldModel::class;

    /** @var Dongtt\LaravelUploadModel\Eloquent\FileModel */
    protected $fileModelInstance;

    /** @test */
    public function testCreateJsonArrayFileOnFieldModel()
    {
        $this->createFileModel();
    }

    /** @test */
    public function testUpdateJsonArrayFileOnFieldModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $file3 = UploadedFile::fake()->image(Str::random(10) . '.png');
            $fileModelInstance
                ->fill([
                    'path' => [
                        'key_test' => 'test',
                        'images' => [
                            $file,
                            $file3,
                        ],
                    ],
                ])
                ->save();

            $this->assertNotTrue(Storage::disk('public')->exists($file2->hashName()));
            $this->assertArrayContains1D([
                'key_test' => 'test',
                'images' => [
                    $file->hashName(),
                    $file3->hashName(),
                ],
                'images_url' => [
                    config('filesystems.disks.public.url') . '/' . $file->hashName(),
                    config('filesystems.disks.public.url') . '/' . $file3->hashName(),
                ],
            ], $fileModelInstance->path);
        });
    }

    /** @test */
    public function testDeleteJsonArrayFileOnFieldModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $fileModelInstance->delete();
            $this->assertNotTrue(Storage::disk('public')->exists($file->hashName()));
            $this->assertNotTrue(Storage::disk('public')->exists($file2->hashName()));
        });
    }

    /** @test */
    public function testToArrayJsonArrayFileOnFieldModel()
    {
        $this->createFileModel(function ($fileModelInstance, $file, $file2) {
            $this->assertArrayContains1D([
                'path' => [
                    'key_test' => 'test',
                    'images' => [
                        $file->hashName(),
                        $file2->hashName(),
                    ],
                    'images_url' => [
                        config('filesystems.disks.public.url') . '/' . $file->hashName(),
                        config('filesystems.disks.public.url') . '/' . $file2->hashName(),
                    ],
                ],
            ], $fileModelInstance->toArray());
        });
    }

    private function createFileModel($callback = null)
    {
        $this->assertTrue($this->model instanceof JsonArrayFileOnFieldModel);

        $file = UploadedFile::fake()->image(Str::random(10) . '.png');
        $file2 = UploadedFile::fake()->image(Str::random(10) . '.png');

        $fileModelInstance = $this->model->create([
            'path' => [
                'key_test' => 'test',
                'images' => [
                    $file,
                    $file2,
                ],
            ],
        ]);

        $this->assertArrayContains1D([
            'key_test' => 'test',
            'images' => [
                $file->hashName(),
                $file2->hashName(),
            ],
            'images_url' => [
                config('filesystems.disks.public.url') . '/' . $file->hashName(),
                config('filesystems.disks.public.url') . '/' . $file2->hashName(),
            ],
        ], $fileModelInstance->path);

        if ($callback) {
            $callback($fileModelInstance, $file, $file2);
        }
    }
}
