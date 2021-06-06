<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class JsonFileModelCustomFolder extends FileModel
{

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['path'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'path' => 'array',
    ];

    /**
     * Default save on disk (from keys of app/config/filesystem.php > disks)
     *
     * @var string
     */
    protected $saveOnDisk = 'public';

    /**
     * Save path file to folder
     *
     * @var string
     */
    protected $fileFolders = [
        'path.first' => 'first',
        'path.second' => 'second',
    ];

    /**
     * Save path to column name
     *
     * @var array
     */
    protected $fileFields = ['path.first', 'path.second'];

    public function configFileFolders($fileFolders)
    {
        $this->fileFolders = $fileFolders;
    }

    public function preparePathFirstFileName($fileName)
    {
        return 'bcsok' . $fileName;
    }

    public function isForceDeleting()
    {
        return false;
    }
}
