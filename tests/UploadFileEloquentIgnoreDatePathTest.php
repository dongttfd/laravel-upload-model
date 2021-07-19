<?php

namespace DongttFd\LaravelUploadModel\Test;

use DongttFd\LaravelUploadModel\Test\Models\FileLocalModelIgnoreDatePath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadFileEloquentIgnoreDatePathTest extends TestCase
{
    /** @var Dongtt\LaravelUploadModel\Eloquent\FileModel */
    protected $fileModelInstance;

    /** @test */
    public function testCreateFileOnLocalDiskIgnoreDatePath()
    {
        $this->modelName = FileLocalModelIgnoreDatePath::class;
        $this->makeModel();
        $file = $this->fakeFileModelInstance();
        $this->assertTrue($this->fileModelInstance instanceof FileLocalModelIgnoreDatePath);
        $this->assertTrue(Storage::disk('local')->exists('/files/' . $file->hashName()));
        $this->assertTrue(
            $this->fileModelInstance->path_url == '/storage/files/' . $file->hashName()
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
