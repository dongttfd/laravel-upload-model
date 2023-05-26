<?php

namespace DongttFd\LaravelUploadModel\Eloquent;

use Illuminate\Support\Arr;

trait UploadFileJsonEloquent
{
    /**
     * Save file paths to $value
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function saveFilePathToArray($field, $value)
    {
        $fieldsMapped = $this->getFieldValueMappers($field, $value);

        foreach ($fieldsMapped as $mapped) {
            $keyOfFile = $mapped['keyOfFile'];

            $this->forgetField($field, $keyOfFile);

            $newPath = $this->saveFilePath(
                $mapped['fileField'],
                Arr::get($value, $keyOfFile)
            );

            Arr::set($value, $keyOfFile, $newPath);

            $oldPath = Arr::get(parent::getAttribute($field), $keyOfFile);

            if ($oldPath && $oldPath !== $newPath) {
                $this->filePathOnTrash[] = $oldPath;
            }
        }

        $this->removeFileWhenArrayEmpty($field, $value);

        return $value;
    }

    /**
     * Forget field has suffix `url`
     *
     * @param string $field
     * @param string $keyOfFile
     * @return void
     */
    private function forgetField($field, $keyOfFile)
    {
        if (intval($keyOfFile) == $keyOfFile && strpos($field, '.') === false) {
            unset($this->attributes[$field . '_url']);

            return;
        }

        Arr::forget(
            $this->attributes,
            $this->prepareFileFieldUrl(
                $keyOfFile
                    ? $field . '.' . $keyOfFile
                    : $field
            )
        );
    }

    /**
     * Remove url when file save on json array (*)
     * Prepare file before delete on trash
     *
     * @param string $field
     * @param mixed $value
     */
    private function removeFileWhenArrayEmpty($field, &$value)
    {
        $fileFields = $this->getFileFields($field);
        $oldValue = parent::getAttribute($field) ?? [];
        $oldPaths = [];

        foreach ($fileFields as $fileField) {
            $fileField = trim(substr($fileField, strlen($field . '.')), '.*');
            $this->forgetField($field, $fileField);

            if (!$fileField && empty($value)) {
                $this->forgetField($field, '');
                $oldPaths = array_merge($oldPaths, $oldValue);
                continue;
            }

            if ($fileField && empty(Arr::get($value, $fileField))) {
                $oldPaths = array_merge($oldPaths, Arr::get($oldValue, $fileField) ?? []);
            }
        }

        $this->filePathOnTrash = array_merge(
            $this->filePathOnTrash,
            $oldPaths
        );
    }

    /**
     * Prepare file field url: Eg: ['path.0.image.1'] => ['path.0.image_url.1']
     *
     * @param string $field
     * @return string
     */
    private function prepareFileFieldUrl($field)
    {
        $fieldExploded = explode('.', $field);
        for ($i = (sizeof($fieldExploded) - 1); $i >= 0; $i--) {
            if (strval(intval($fieldExploded[$i])) !== $fieldExploded[$i]) {
                $fieldExploded[$i] = $fieldExploded[$i] . '_url';
                break;
            }
        }

        return implode('.', $fieldExploded);
    }

    /**
     * Assign field with url to array
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function assignFileFieldUrlToArray($field, $value)
    {
        $fieldsMapped = $this->getFieldValueMappers($field, $value);

        foreach ($fieldsMapped as $mapped) {
            $fieldKey = $field . '.' . $mapped['keyOfFile'];
            $path = Arr::get($value, $mapped['keyOfFile']);
            $fileUrl = $path ? $this->getUrl($path) : null;
            $fieldUrl = $this->prepareFileFieldUrl($fieldKey);

            if (hasPrefix($fieldUrl, $field . '_url')) {
                Arr::set(
                    $this->attributes,
                    $fieldUrl,
                    $fileUrl
                );
            } else {
                Arr::set(
                    $value,
                    $this->prepareFileFieldUrl($mapped['keyOfFile']),
                    $fileUrl
                );
            }
        }

        $this->addUrlFieldWhenArrayEmpty($field, $value);

        $this->attributes[$field] = json_encode($value);

        return $value;
    }

    /**
     * Get field with key (dot type) of value
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    private function getFieldValueMappers($field, $value)
    {
        $fileFields = $this->getFileFields($field);

        $fileFieldMappers = [];
        foreach ($fileFields as $fileField) {
            $subFileField = substr($fileField, strlen($field . '.'));
            foreach (array_keys(Arr::dot($value)) as $keyOfFile) {
                if (preg_match($this->makeFieldRegex($subFileField), $keyOfFile)) {
                    $fileFieldMappers[] = compact('fileField', 'keyOfFile');
                }
            }
        }

        return $fileFieldMappers;
    }

    /**
     * Prepare path file to delete
     *
     * @param string $field
     */
    private function prepareFileOnJsonToDelete($field)
    {
        $value = parent::getAttribute($field);
        $fieldsMapped = $this->getFieldValueMappers($field, $value);

        foreach ($fieldsMapped as $mapped) {
            $this->filePathOnTrash[] = Arr::get($value, $mapped['keyOfFile']);
        }
    }

    /**
     * Assign url field when array file empty (*)
     *
     * @param string $field
     * @param mixed &$value
     */
    private function addUrlFieldWhenArrayEmpty($field, &$value)
    {
        $fileFields = $this->getFileFields($field);

        foreach ($fileFields as $fileField) {
            $fileField = trim(substr($fileField, strlen($field . '.')), '.*');

            if (!$fileField && empty($value)) {
                $this->attributes["{$field}_url"] = [];
                continue;
            }

            if ($fileField && empty(Arr::get($value, $fileField))) {
                Arr::set($value, "{$fileField}_url", []);
            }
        }
    }

    /**
     * Max regex of file field: eg 'images.*' => '/^image.\\d+$//'
     *
     * @param string $field
     * @return string
     */
    private function makeFieldRegex($field)
    {
        return '/^' . str_replace('*', '\\d+', $field) . '$/';
    }
}
