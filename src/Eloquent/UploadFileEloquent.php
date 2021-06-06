<?php

namespace DongttFd\LaravelUploadModel\Eloquent;

use DongttFd\LaravelUploadModel\Exceptions\UploadEloquentException;
use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait UploadFileEloquent
{
    use FilesystemEloquent, UploadFileJsonEloquent;

    /**
     * Default save on disk (from keys of app/config/filesystem.php > disks)
     *
     * @var string
     */
    protected $saveOnDisk = null;

    /**
     * Save path to columns name
     * Eg: [
     *    'path', // column name
     *    'path.*', // column name with json
     * ]
     *
     * @var array
     */
    protected $fileFields = [];

    /**
     * Save path file to folder, format: ['<file-field>' => 'folder-name']
     *
     * @var array
     */
    protected $fileFolders = [];

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
     * Check and save file to disk return value to save columns
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function saveFileField($field, $value)
    {
        $fileFields = $this->getFileFields($field);

        if (!$this->isJsonFileField($fileFields)) {
            if ($oldPath = parent::getAttribute($field)) {
                $this->filePathOnTrash[] = $oldPath;
            }

            unset($this->attributes[$field . '_url']);

            return $this->saveFilePath($field, $value);
        }

        return $this->saveFilePathToArray($field, $value);
    }

    /**
     * Check all file fields is of json field or not
     *
     * @param array $fileFields
     * @return boolean
     */
    private function isJsonFileField($fileFields)
    {
        return sizeof($fileFields) !== 1 || Str::contains($fileFields[0], ['.', '*']);
    }

    /**
     * Save file path if change and return path
     *
     * @param string $field
     * @param mixed $file
     * @return string | null
     *
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function saveFilePath($field, $file)
    {
        $file = $this->prepareFile($field, $file);

        if (!($file instanceof UploadedFile)) {
            return $file;
        }

        return $this->upload(
            $file,
            $this->prepareFolder($field) ?? '',
            $this->prepareFileName($field, $file->getClientOriginalName())
        );
    }

    /**
     * Prepare file before upload
     *
     * @param string $field
     * @param mixed $file
     * @return string | UploadedFile | null
     *
     * @throws UploadEloquentException
     */
    private function prepareFile($field, $file)
    {
        if (!$file) {
            return null;
        }

        if (!($file instanceof UploadedFile) && !is_string($file)) {
            throw new UploadEloquentException(get_class($this) . "::{$field} must is string or is instance of " . UploadedFile::class);
        }

        if (is_string($file) && !$this->exists($file)) {
            throw new UploadEloquentException(get_class($this) . "::{$field} have must existed on your disk");
        }

        return $file;
    }

    /**
     * Get folder of file are going to save field
     *
     * @param string $field
     * @return mixed
     *
     * @throws UploadEloquentException
     */
    private function prepareFolder($field)
    {
        $folder = $this->fileFolders[$field] ?? null;

        if ($folder && !is_string($folder)) {
            throw new UploadEloquentException(get_class($this) . "::fileFolders['{$field}'] have must is string");
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
        $customFileNameFunction = 'prepare' . toPasscalCase($field) . 'FileName';

        return method_exists($this, $customFileNameFunction)
            ? $this->{$customFileNameFunction}($currentFileName)
            : null;
    }

    /**
     * Get file field configuration of model
     *
     * @param string | null $field
     * @return array
     */
    public function getFileFields($field = null): array
    {
        $fileFields = $this->fileFields ?? [];
        if (!$field) {
            return $fileFields;
        }

        return array_values(array_filter(
            $fileFields,
            function ($fileField) use ($field) {
                if (!is_string($fileField) || $fileField === '') {
                    throw new UploadEloquentException(
                        'Element in ' . get_class($this) . '::$fileFields must is String'
                    );
                }

                return hasPrefix($fileField, $field);
            })
        );
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
                $columName = explode('.', $field)[0];
                if (!$this->isJsonFileField($this->getFileFields($columName))) {
                    $this->filePathOnTrash[] = $this->getOriginal($field);
                    break;
                }

                $this->prepareFileOnJsonToDelete($columName);
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
            && !Str::contains($field, ['.', '*'])
            && $this->isFileField($fieldName = str_replace('_url', '', $field))
        ) {
            $value = parent::getAttribute($fieldName);

            $this->assignFileFieldUrl($fieldName, $value);

            return $this->attributes[$field];
        }

        $value = parent::getAttribute($field);

        if ($this->isFileField($field)) {
            $value = $this->assignFileFieldUrl($field, $value);
        }

        return $value;
    }

    /**
     * Swich file fields of field and assign url to that
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function assignFileFieldUrl($field, $value)
    {
        $fileFields = $this->getFileFields($field);

        if (!$this->isJsonFileField($fileFields) && !is_array($value)) {
            $this->attributes[$field . '_url'] = $value ? $this->getUrl($value) : null;

            return $value;
        }

        return $this->assignFileFieldUrlToArray($field, $value);
    }

    /**
     * Check field is file field
     *
     * @param string $field
     * @return bool
     */
    private function isFileField($field)
    {
        return !empty($this->getFileFields($field));
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
        if ($this->isfileField($key)) {
            $value = $this->saveFileField($key, $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Overide toArray: refresh model, get file fields and conver to Array
     *
     * @return array
     */
    public function toArray()
    {
        $fileFields = array_unique(array_map(
            function ($field) {
                return explode('.', $field)[0];
            },
            $this->getFileFields()
        ));

        foreach ($fileFields as $field) {
            $this->{$field};
        }

        return parent::toArray();
    }
}
