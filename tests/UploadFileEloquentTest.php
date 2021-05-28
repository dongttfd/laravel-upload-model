<?php

namespace DongttFd\LaravelUploadModel\Test;

use Mockery\MockInterface;
use Mockery;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Exception;
use DongttFd\LaravelUploadModel\Test\Models\FileS3Model;
use DongttFd\LaravelUploadModel\Test\Models\FilePublicModel;
use DongttFd\LaravelUploadModel\Test\Models\FileLocalModel;

class UploadFileEloquentTest extends TestCase
{
    /** @var Dongtt\LaravelUploadModel\Eloquent\FileModel */
    protected $fileModelInstance;

    /** @var string */
    protected $modelName = FilePublicModel::class;

    /** @test */
    public function testDefaultLocalDisk()
    {
        $this->fileModelInstance = new FileLocalModel();
        $this->fileModelInstance->setSaveOnDiskNull();

        $storage = Storage::fake('local');
        Storage::shouldReceive('disk')->andReturn($storage);
        $this->fileModelInstance
            ->fill([
                'path' => UploadedFile::fake()->image(Str::random(12) . '.png'),
            ])
            ->save();
    }

    /** @test */
    public function testDefaultS3Disk()
    {
        $this->fileModelInstance = new FileLocalModel();
        $this->fileModelInstance->setSaveOnDiskNull();
        config('filesystems.default', null);
        $storage = Storage::fake('s3');
        Storage::shouldReceive('disk')->andReturn($storage);
        $this->fileModelInstance
            ->fill([
                'path' => UploadedFile::fake()->image(Str::random(12) . '.png'),
            ])
            ->save();
    }

    /** @test */
    public function testCreateFileOnPublicDisk()
    {
        $file = $this->fakeFileModelInstance();
        $this->assertTrue($this->fileModelInstance instanceof FilePublicModel);
        $this->assertTrue(Storage::disk('public')->exists('files/' . $file->hashName()));
        $this->assertTrue(
            $this->fileModelInstance->path_url == config('filesystems.disks.public.url') . '/files/' . $file->hashName()
        );
    }

    /** @test */
    public function testCreateFileOnLocalDisk()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();
        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $file->hashName()));
        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $file->hashName()
        );
    }

    /** @test */
    public function testCreateFileOnS3Disk()
    {
        $fakeFilesystem = Mockery::mock(Storage::fake('s3'), function (MockInterface $mock) {
            $mock->shouldReceive('temporaryUrl')->andReturn('s3-service-url');
        });
        Storage::shouldReceive('disk')->andReturn($fakeFilesystem);

        $this->modelName = FileS3Model::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();

        $this->assertTrue($this->fileModelInstance instanceof FileS3Model);
        $this->assertTrue(Storage::disk('s3')->exists('/files/' . $file->hashName()));
        $this->assertTrue(
            $this->fileModelInstance->path_url == 's3-service-url'
        );
    }

    /** @test */
    public function testUpdateFile()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $this->fileModelInstance = new FileLocalModel;

        $fileUpdate = UploadedFile::fake()->image(Str::random(20) . '.jpg');
        $this->fileModelInstance->fill(['path' => $fileUpdate])->save();
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $fileUpdate->hashName()));
    }

    /** @test */
    public function testUpdateAndDeleteOldFile()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();
        $filePath = '/files/' . $file->hashName();

        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists($filePath));

        $fileUpdate = UploadedFile::fake()->image(Str::random(20) . '.jpg');
        $this->fileModelInstance->update(['path' => $fileUpdate]);

        $this->assertNotTrue(Storage::disk('local')->exists($filePath));
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $fileUpdate->hashName()));
    }

    /** @test */
    public function testFillPathWasNotStringOrFile()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $this->fileModelInstance = new FileLocalModel;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File path must is string or is instance of Illuminate\Http\UploadedFile');
        $this->fileModelInstance->fill(['path' => 123])->save();
    }

    /** @test */
    public function testFillPathNotExistedOnStorage()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $this->fileModelInstance = new FileLocalModel;
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File path have must existed on your disk');
        $this->fileModelInstance->fill(['path' => 'files/' . Str::random(12) . '.png'])->save();
        // Arr::set($array, 'products.desk.price', 200);
    }

    /** @test */
    public function testFillPathFileExistedOnStorage()
    {
        $storage = Storage::fake('local');
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = UploadedFile::fake()->image(Str::random(15) . '.jpg');
        $filePath = 'files/' . $file->name;
        $storage->put($filePath, $file);
        $this->fileModelInstance = new FileLocalModel;
        $this->fileModelInstance->fill(['path' => $filePath])->save();
        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/' . $filePath
        );
    }

    /** @test */
    public function fileExistedOnDatabaseButNotExistOnDisk()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();
        $filePath = '/files/' . $file->hashName();

        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists($filePath));

        Storage::disk('local')->delete($this->fileModelInstance->path);
        $this->assertNull($this->fileModelInstance->path_url);
    }

    /** @test */
    public function testfileRetryFile()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = $this->fakeFileModelInstance();
        $filePath = '/files/' . $file->hashName();

        $this->assertTrue(
            $this->fileModelInstance->retrieving($filePath) == $file->getContent()
        );
    }

    /** @test */
    public function testfileRetryFileNotExisted()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = $this->fakeFileModelInstance();
        $filePath = '/files/' . $file->hashName();
        Storage::disk('local')->delete($filePath);

        $this->assertTrue(
            $this->fileModelInstance->retrieving($filePath) == null
        );
    }

    /** @test */
    public function testToArray()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = $this->fakeFileModelInstance();
        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $file->hashName()
        );

        $this->assertEquals(
            $this->fileModelInstance->toArray(),
            [
                'id' => 1,
                'path' => $this->fileModelInstance->path,
                'path_url' => $this->fileModelInstance->path_url,
            ]
        );
    }

    /** @test */
    public function testDeleteFileModelInstance()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = $this->fakeFileModelInstance();
        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $file->hashName()));

        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $file->hashName()
        );

        $this->fileModelInstance->delete();
        $this->assertFalse(Storage::disk('local')->exists('/files/' . $file->hashName()));
    }

    /** @test */
    public function testFileNull()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $fileModelInstance = $this->model->create(['path' => null]);
        $this->assertTrue($fileModelInstance instanceof FileLocalModel);
        $this->assertNull($fileModelInstance->path);
        $this->assertNull($fileModelInstance->path_url);
    }

    /**
     * Fake TestFileModel instance and return file
     *
     * @return \Illuminate\Http\Testing\File
     */
    private function fakeFileModelInstance()
    {
        $file = UploadedFile::fake()->image(Str::random(10) . '.png');

        $this->fileModelInstance = $this->model->create([
            'path' => $file,
        ]);

        return $file;
    }
}
