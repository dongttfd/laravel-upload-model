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
        $fieldsMapped = $this->getFieldValueMappeds($field, $value);

        foreach ($fieldsMapped as $mapped) {
            $keyOfFile = $mapped['keyOfFile'];
            Arr::forget(
                $this->attributes,
                $this->prepareFileFieldUrl($field . '.' . $keyOfFile)
            );

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

        return $value;
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
        $fieldsMapped = $this->getFieldValueMappeds($field, $value);

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
    private function getFieldValueMappeds($field, $value)
    {
        $fileFields = $this->getFileFields($field);

        $fileFieldMappeds = [];
        foreach ($fileFields as $fileField) {
            $subFileField = substr($fileField, strlen($field . '.'));
            foreach (array_keys(Arr::dot($value)) as $keyOfFile) {
                if (preg_match($this->makeFieldRegex($subFileField), $keyOfFile)) {
                    $fileFieldMappeds[] = compact('fileField', 'keyOfFile');
                }
            }
        }

        return $fileFieldMappeds;
    }

    /**
     * Prepare path file to delete
     *
     * @param string $field
     * @return void
     */
    private function prepareFileOnJsonToDelete($field)
    {
        $value = parent::getAttribute($field);
        $fieldsMapped = $this->getFieldValueMappeds($field, $value);

        foreach ($fieldsMapped as $mapped) {
            $this->filePathOnTrash[] = Arr::get($value, $mapped['keyOfFile']);
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
