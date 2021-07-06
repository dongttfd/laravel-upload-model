<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class FileLocalModel extends FileModel
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
     * Set saveOnDisk property is null
     *
     * @return void
     */
    public function configSaveOnDiskNull()
    {
        $this->saveOnDisk = null;
    }
}
