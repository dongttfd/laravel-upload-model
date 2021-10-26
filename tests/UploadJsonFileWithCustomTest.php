<?php

namespace DongttFd\LaravelUploadModel\Test;

use DongttFd\LaravelUploadModel\Exceptions\UploadEloquentException;
use DongttFd\LaravelUploadModel\Test\Models\JsonFileModelCustomFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadJsonFileWithCustomTest extends TestCase
{
    /** @var string */
    protected $modelName = JsonFileModelCustomFolder::class;

    /** @var Dongtt\LaravelUploadModel\Eloquent\FileModel */
    protected $fileModelInstance;

    /** @test */
    public function testExceptionsFileFolders()
    {
        $this->assertTrue($this->model instanceof JsonFileModelCustomFolder);
        $this->model->configFileFolders(['path.first' => 1]);
        $this->expectExceptionMessage(JsonFileModelCustomFolder::class . "::fileFolders['path.first'] have must is string");

        $this->expectException(UploadEloquentException::class);
        $this->model->fill(['path' => [
            'first' => UploadedFile::fake()->image(Str::random(10) . '.png'),
        ]]);
    }

    /** @test */
    public function testSoftDelete()
    {
        $this->assertTrue($this->model instanceof JsonFileModelCustomFolder);
        $file = UploadedFile::fake()->image(Str::random(10) . '.png');
        $file2 = UploadedFile::fake()->image(Str::random(10) . '.png');

        $fileModelInstance = $this->model->create([
            'path' => [
                'other' => 'bcsok',
                'first' => $file,
                'second' => $file2,
                'images' => [],
            ],
        ]);

        $this->assertArrayContains1D([
            'other' => 'bcsok',
            'first' => 'first/' . $this->setCurrentDateFolder('bcsok' . $file->name),
            'second' => 'second/' . $this->setCurrentDateFolder($file2->hashName()),
            'first_url' => config('filesystems.disks.public.url') . '/first/' . $this->setCurrentDateFolder('bcsok' . $file->name),
            'second_url' => config('filesystems.disks.public.url') . '/second/' . $this->setCurrentDateFolder($file2->hashName()),
            'images' => [],
            'images_url' => [],
        ], $fileModelInstance->path);

        $fileModelInstance->delete();
        $this->assertTrue(Storage::disk('public')->exists('first/' . $this->setCurrentDateFolder('bcsok' . $file->name)));
        $this->assertTrue(Storage::disk('public')->exists('second/' . $this->setCurrentDateFolder($file2->hashName())));
    }
}
