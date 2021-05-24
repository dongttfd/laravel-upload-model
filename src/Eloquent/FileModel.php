<?php

namespace DongttFd\LaravelUploadModel\Eloquent;

use DongttFd\LaravelUploadModel\Contracts\UploadOnEloquentModel;
use DongttFd\LaravelUploadModel\Eloquent\UploadFileEloquent;
use Illuminate\Database\Eloquent\Model;

class FileModel extends Model implements UploadOnEloquentModel
{
    use UploadFileEloquent;
}
