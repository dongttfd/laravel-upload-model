# Upload file and assign to attributes of eloquent model


## Laravel Support
- Suport laravel >= 5.7


## What It Does
- Easy to **move uploaded file** to your folder via model functions (eg: `create, update, fill, save ...`)
- Convenient with **Auto assign** file path to attributes of eloquent model
- Easy to **retry file** of model


## Installation

**Via Composer**
```bash 
composer require dongttfd/laravel-upload-model
```


## Usage

### Use packages at your model:

```php
...

use DongttFd\LaravelUploadModel\Contracts\UploadOnEloquentModel;
use DongttFd\LaravelUploadModel\Eloquent\UploadFileEloquent;

class User extends Model implements UploadOnEloquentModel
{
    use UploadFileEloquent;


    ...
}
```

### Implement


*Create model with uploaded file*

```php
...

// $file must is instance of Illuminate\Http\UploadedFile
$file = $request->file;

User::create(['path' => $file]);

```

*Update model with uploaded file*


```php
...

// $file must is instance of Illuminate\Http\UploadedFile
$file = $request->file;

User::first()->update(['path' => $file]);

```

*Easy to retries*


```php
...

// $file must is instance of Illuminate\Http\UploadedFile
$file = $request->file;

$user = User::first()->update(['path' => $file]);

// path of file
$user->path;

// url of file
$user->path_url;

```

### Read more configurations of model at: ```UploadFileEloquent```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


## Security

If you discover any security-related issues, please email [My Email](mailto:dongtt.fd@gmail.com) instead of using the issue tracker.


## Testing
```bash
composer test
```