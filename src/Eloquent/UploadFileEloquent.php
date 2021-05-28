<?php

namespace DongttFd\LaravelUploadModel\Eloquent;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait UploadFileEloquent
{
    /**
     * Default save on disk (from keys of app/config/filesystem.php > disks)
     *
     * @var string
     */
    protected $saveOnDisk = null;

    /**
     * Save path file to folder
     *
     * @var string
     */
    protected $fileFolders = ['path' => 'files'];

    /**
     * Save path to column name
     *
     * @var array
     */
    protected $fileFields = ['path'];

    /**
     * Only s3 amazon: save with publish file
     *
     * @var bool
     */
    protected $filePublish = false;

    /**
     * Only s3 amazon: expire time (minutes) off private file
     *
     * @var int
     */
    protected $fileExpireIn = 5;

    /**
     * System storage disk
     *
     * @var Illuminate\Filesystem\FilesystemAdapter | null
     */
    private $storageDisk;

    /**
     * Trash of file not use
     *
     * @var array
     */
    private $filePathOnTrash = [];

    /**
     * Overide boot model to delete file on storage
     *
     * @return void
     */
    protected static function bootUploadFileEloquent()
    {
        self::created(function ($model) {
            $model->deleteOldFiles();
        });

        self::updated(function ($model) {
            $model->deleteOldFiles();
        });

        self::deleted(function ($model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $model->deleteOldFiles(true);
        });
    }

    /**
     * Load configurations
     *
     */
    public function loadStorageConfig(): void
    {
        if (!$this->saveOnDisk) {
            $this->saveOnDisk = config('filesystems.default') ?? 's3';
        }

        // if (method_exists($this, 'saveDiskName')) {
        //     $this->saveDiskName();
        // }
        $this->storageDisk = Storage::disk($this->saveOnDisk);
    }

    /**
     * To save file if change
     *
     * @param string $field
     * @param mixed $file
     * @return string | null
     *
     * @throws Exception
     * @throws FileNotFoundException
     */
    public function saveFilePath($field, $file)
    {
        if (!$file) {
            return null;
        }

        if (!($file instanceof UploadedFile) && !is_string($file)) {
            throw new Exception('File path must is string or is instance of ' . UploadedFile::class);
        }

        if (is_string($file)) {
            if (!$this->exists($file)) {
                throw new FileNotFoundException('File path have must existed on your disk');
            }

            return $file;
        }

        if ($oldPath = parent::getAttribute($field)) {
            $this->filePathOnTrash[] = $oldPath;
        }

        unset($this->attributes[$field . '_url']);

        return $this->upload(
            $file,
            $this->prepareFolder($field) ?? '',
            $this->prepareFileName($field, $file->getClientOriginalName())
        );
    }

    /**
     * Get folder of file are going to save field
     *
     * @param string $field
     * @return mixed
     */
    private function prepareFolder($field)
    {
        $folder = $this->fileFolders[$field] ?? null;

        $customFolderFunction = 'custom' . toPasscalCase($field) . 'Folder';

        if (method_exists($this, $customFolderFunction)) {
            $folder = $this->{$customFolderFunction}($folder);
        }

        return $folder;
    }

    /**
     * Get file name of file are going to save field
     *
     * @param string $field
     * @param string $currentFileName
     * @return mixed
     */
    private function prepareFileName($field, $currentFileName = '')
    {
        $customFileNameFunction = 'custom' . toPasscalCase($field) . 'FileName';

        return method_exists($this, $customFileNameFunction)
            ? $this->{$customFileNameFunction}($currentFileName)
            : null;
    }

    /**
     * Continue to upload
     *
     * @param Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param mixed $fileName
     * @return string
     */
    public function upload(UploadedFile $file, $folder = '', $fileName = null): string
    {
        if (!$this->storageDisk) {
            $this->loadStorageConfig();
        }

        return $fileName
            ? $this->storageDisk->putFileAs(
            $folder,
            $file,
            $fileName,
            $this->filePublish ? 'public' : null
        )
            : $this->storageDisk->put(
            $folder,
            $file,
            $this->filePublish ? 'public' : null
        );
    }

    /**
     * Delete file on storage disk
     *
     * @param string $path
     * @return mixed
     */
    public function deleteFile($path)
    {
        return $this->exists($path)
            ? $this->storageDisk->delete($path)
            : null;
    }

    /**
     * Retrieve the contents of a file
     *
     * @param string $path
     * @return string | null
     */
    public function retrieving($path)
    {
        if (!$this->exists($path)) {
            return null;
        }

        return $this->storageDisk->get($path);
    }

    /**
     * Get full url file
     *
     * @param string $path
     * @return string | null
     */
    public function getUrl($path)
    {
        if (!$this->exists($path)) {
            return null;
        }

        if ($this->saveOnDisk === 's3' && !$this->filePublish) {
            return $this->retrievingTemporaryUrlS3($path);
        }

        return $this->storageDisk->url($path);
    }

    /**
     * Check file existed on storage disk
     *
     * @param string $path
     * @return bool
     */
    private function exists($path): bool
    {
        if (!$this->storageDisk) {
            $this->loadStorageConfig();
        }

        return $this->storageDisk->exists($path);
    }

    /**
     * Get temporay url from s3 of private file
     *
     * @param string $path
     * @return string
     */
    private function retrievingTemporaryUrlS3($path)
    {
        return $this->storageDisk->temporaryUrl(
            $path,
            now()->addMinutes($this->fileExpireIn)
        );
    }

    /**
     * Get path of file from database
     *
     * @return array
     */
    public function getFileFields(): array
    {
        return $this->fileFields ?? ['path'];
    }

    /**
     * After change database to delete old file
     *
     * @param bool $all
     * @return void
     */
    private function deleteOldFiles($isDeletedAction = false)
    {
        if ($isDeletedAction) {
            foreach ($this->getFileFields() as $field) {
                $this->filePathOnTrash[] = $this->getOriginal($field);
            }
        }

        foreach ($this->filePathOnTrash as $path) {
            if ($path) {
                $this->deleteFile($path);
            }
        }

        $this->filePathOnTrash = [];
    }

    /**
     * Overide get attribute
     *
     * @param string $field
     * @return mixed
     */
    public function getAttribute($field)
    {
        if (hasSubfix($field, '_url')
            && $this->isFileField($fieldName = str_replace('_url', '', $field))
        ) {
            $value = parent::getAttribute($fieldName);

            return $this->attributes[$field] = $value ? $this->getUrl($value) : null;
        }

        $value = parent::getAttribute($field);

        if ($this->isFileField($field)) {
            $this->attributes[$field . '_url'] = $value ? $this->getUrl($value) : null;
        }

        return $value;
    }

    /**
     * Check field is file field
     *
     * @param string $field
     * @return bool
     */
    private function isFileField($field)
    {
        return in_array($field, $this->getFileFields());
    }

    /**
     * Overide set attribute
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->getFileFields())) {
            $value = $this->saveFilePath($key, $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Overide toArray
     *
     * @return array
     */
    public function toArray()
    {
        $fileFields = $this->getFileFields();

        foreach ($fileFields as $field) {
            $this->{$field};
        }

        return parent::toArray();
    }
}
