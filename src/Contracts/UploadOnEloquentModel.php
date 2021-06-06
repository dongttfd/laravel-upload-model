<?php

namespace DongttFd\LaravelUploadModel\Contracts;

use Illuminate\Http\UploadedFile;

interface UploadOnEloquentModel
{
    /**
     * Load configurations
     *
     */
    public function loadStorageConfig(): void;

    /**
     * Continue to upload
     *
     * @param Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param mix $fileName
     * @return string
     */
    public function upload(UploadedFile $file, $folder = '', $fileName = null): string;

    /**
     * Delete file on storage disk
     *
     * @param string $path
     * @return mix
     */
    public function deleteFile($path);

    /**
     * Retrieve the contents of a file
     *
     * @param string $path
     * @return string | null
     */
    public function retrieving($path);

    /**
     * Get full url file
     *
     * @param string $path
     * @return string
     */
    public function getUrl($path);

    /**
     * Get path of file from database
     *
     * @throws DongttFd\LaravelUploadModel\Exceptions\UploadEloquentException
     * @return array
     */
    public function getFileFields(): array;

    /**
     * Check and save file to disk return value to save columns
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    public function saveFileField($field, $value);

    /**
     * To save file if change
     *
     * @param string $field
     * @param mix $file
     * @return string | null
     *
     * @throws Exception
     * @throws FileNotFoundException
     */
    public function saveFilePath($field, $file);
}
