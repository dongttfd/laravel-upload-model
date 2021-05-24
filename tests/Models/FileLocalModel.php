<?php

namespace DongttFd\LaravelUploadModel\Test\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class FileLocalModel extends FileModel
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
    protected $saveOnDisk = 'local';

    /**
     * Set saveOnDisk property is null
     *
     * @return void
     */
    public function setSaveOnDiskNull()
    {
        $this->saveOnDisk = null;
    }

    public function customPathFileName($fileName)
    {
        return null;
    }
}
