<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class JsonArrayFileOnFieldModel extends FileModel
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
     * Save path to column name
     *
     * @var array
     */
    protected $fileFields = ['path.images.*'];

    /**
     * Save path file to folder
     *
     * @var string
     */
    protected $fileFolders = ['path' => 'files'];

}
