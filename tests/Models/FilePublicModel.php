<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class FilePublicModel extends FileModel
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
     * Default save on disk (from keys of app/config/filesystem.php > disks)
     *
     * @var string
     */
    protected $saveOnDisk = 'public';

    // /**
    //  * Save path file to folder
    //  *
    //  * @var string
    //  */
    // protected $fileFolders = ['path' => 'files'];

    // /**
    //  * Save path to column name
    //  *
    //  * @var array
    //  */
    // protected $fileFields = ['path'];

    // /**
    //  * Only s3 amazon: expire time (minutes) off private file
    //  *
    //  * @var int
    //  */
    // protected $fileExpireIn = 5;

    // /**
    //  * Only s3 amazon: save with publish file
    //  *
    //  * @var bool
    //  */
    // protected $filePublish = false;

    // /**
    //  * System storage disk
    //  *
    //  * @var Illuminate\Filesystem\FilesystemAdapter | null
    //  */
    // protected $storageDisk;

    // /**
    //  * Change disk when save of file (from keys of app/config/filesystem.php > disks)
    //  *
    //  * @param string $disk
    //  */
    // public function setDisk(string $disk)
    // {
    //     $this->saveOnDisk = $disk;
    //     $this->storageDisk = null;
    // }
}
