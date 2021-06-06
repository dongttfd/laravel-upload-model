<?php

namespace DongttFd\LaravelUploadModel\Eloquent;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FilesystemEloquent
{
    /**
     * Load disk configurations
     *
     */
    public function loadStorageConfig(): void
    {
        if (!$this->saveOnDisk) {
            $this->saveOnDisk = config('filesystems.default') ?? 's3';
        }

        $this->storageDisk = Storage::disk($this->saveOnDisk);
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
}
