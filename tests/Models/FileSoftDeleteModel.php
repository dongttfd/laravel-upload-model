<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class FileSoftDeleteModel extends FileModel
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
    protected $fillable = [
        'name',
        'path',
        'avatar',
    ];

    /**
     * Save path to column name
     *
     * @var array
     */
    protected $fileFields = [
        'path',
        'avatar',
    ];

    /**
     * Save path file to folder
     *
     * @var string
     */
    protected $fileFolders = ['path' => 'files'];

    /**
     * Default save on disk (from keys of app/config/filesystem.php > disks)
     *
     * @var string
     */
    protected $saveOnDisk = 'local';

    /**
     * Use flag to delete file when create, update, delete object
     *
     * @var bool
     */
    protected $forceDeleteFile = false;

    /**
     * Set saveOnDisk property is null
     *
     * @return void
     */
    public function configSaveOnDiskNull()
    {
        $this->saveOnDisk = null;
    }
}
