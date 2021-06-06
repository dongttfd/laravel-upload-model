<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class FileS3Model extends FileModel
{
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['path'];

    /**
     * Default save on disk (from keys of app/config/filesystem.php > disks)
     *
     * @var string
     */
    protected $saveOnDisk = 's3';

    /**
     * Save path to column name
     *
     * @var array
     */
    protected $fileFields = [
        'path',
    ];

    /**
     * Save path file to folder
     *
     * @var string
     */
    protected $fileFolders = ['path' => 'files'];
}
