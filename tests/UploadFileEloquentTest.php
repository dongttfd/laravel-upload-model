<?php

namespace DongttFd\LaravelUploadModel\Test;

use DongttFd\LaravelUploadModel\Exceptions\UploadEloquentException;
use DongttFd\LaravelUploadModel\Test\Models\FileLocalModel;
use DongttFd\LaravelUploadModel\Test\Models\FilePublicModel;
use DongttFd\LaravelUploadModel\Test\Models\FileS3Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;

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
        $this->fileModelInstance->configSaveOnDiskNull();

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
        $this->fileModelInstance->configSaveOnDiskNull();
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
        $this->assertTrue(Storage::disk('public')->exists('files/' . $this->setCurrentDateFolder($file->hashName())));
        $this->assertTrue(
            $this->fileModelInstance->path_url == config('filesystems.disks.public.url') . '/files/' . $this->setCurrentDateFolder($file->hashName())
        );
    }

    /** @test */
    public function testCreateFileOnLocalDisk()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();
        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($file->hashName())));
        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $this->setCurrentDateFolder($file->hashName())
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
        $this->assertTrue(Storage::disk('s3')->exists('/files/' . $this->setCurrentDateFolder($file->hashName())));
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
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($fileUpdate->hashName())));
    }

    /** @test */
    public function testUpdateFileObject()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $this->fileModelInstance = new FileLocalModel;

        $fileUpdate = UploadedFile::fake()->image(Str::random(20) . '.jpg');
        $this->fileModelInstance->fill(['path' => $fileUpdate])->save();

        $this->fileModelInstance->update([
            'name' => 'bcsok',
            'path' => $this->fileModelInstance->path,
        ]);

        $this->assertTrue(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($fileUpdate->hashName())));
    }

    /** @test */
    public function testUpdateAndDeleteOldFile()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();
        $filePath = '/files/' . $this->setCurrentDateFolder($file->hashName());

        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists($filePath));

        $fileUpdate = UploadedFile::fake()->image(Str::random(20) . '.jpg');
        $this->fileModelInstance->update(['path' => $fileUpdate]);

        $this->assertNotTrue(Storage::disk('local')->exists($filePath));
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($fileUpdate->hashName())));
    }

    /** @test */
    public function testFillPathWasNotStringOrFile()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $this->fileModelInstance = new FileLocalModel;
        $this->expectException(UploadEloquentException::class);
        $this->expectExceptionMessage(FileLocalModel::class . '::path must is string or is instance of Illuminate\Http\UploadedFile');
        $this->fileModelInstance->fill(['path' => 123])->save();
    }

    /** @test */
    public function testFillPathNotExistedOnStorage()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();
        $this->fileModelInstance = new FileLocalModel;
        $this->expectException(UploadEloquentException::class);
        $this->expectExceptionMessage(FileLocalModel::class . '::path have must existed on your disk');
        $this->fileModelInstance->fill(['path' => 'files/' . Str::random(12) . '.png'])->save();
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
        $filePath = '/files/' . $this->setCurrentDateFolder($file->hashName());

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
        $filePath = '/files/' . $this->setCurrentDateFolder($file->hashName());

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
        $filePath = '/files/' . $this->setCurrentDateFolder($file->hashName());
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
            $this->fileModelInstance->path_url == '/storage/files/' . $this->setCurrentDateFolder($file->hashName())
        );

        $this->assertArrayContains1D(
            [
                'id' => 1,
                'path' => 'files/' . $this->setCurrentDateFolder($file->hashName()),
                'path_url' => '/storage/files/' . $this->setCurrentDateFolder($file->hashName()),
            ],
            $this->fileModelInstance->toArray()
        );
    }

    /** @test */
    public function testDeleteFileModelInstance()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = $this->fakeFileModelInstance();
        $this->assertTrue($this->fileModelInstance instanceof FileLocalModel);
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($file->hashName())));

        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $this->setCurrentDateFolder($file->hashName())
        );

        $this->fileModelInstance->delete();
        $this->assertFalse(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($file->hashName())));
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

    /** @test */
    public function testMultipleFileField()
    {
        $this->modelName = FileLocalModel::class;
        $this->makeModel();

        $file = UploadedFile::fake()->image(Str::random(10) . '.png');
        $avatar = UploadedFile::fake()->image(Str::random(10) . '.png');
        $this->fileModelInstance = $this->model->create([
            'path' => $file,
            'avatar' => $avatar,
        ]);

        $this->assertTrue(Storage::disk('local')->exists('/files/' . $this->setCurrentDateFolder($file->hashName())));
        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $this->setCurrentDateFolder($file->hashName())
        );

        $this->assertTrue(Storage::disk('local')->exists($this->setCurrentDateFolder($avatar->hashName())));
        $this->assertTrue(
            $this->fileModelInstance->avatar_url == '/storage/' . $this->setCurrentDateFolder($avatar->hashName())
        );
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
