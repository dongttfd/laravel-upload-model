# Upload file on eloquent model


## Laravel Support
- `"laravel/framework": "^5.7|^6.0|^7.0|^8.0|^9.0|^10.0"`


## What It Does
- Easy and fast to **move uploaded file** to your folder via model functions (eg: `create, update, fill, save ...`)
- Convenient with **Auto assign** file path to attributes of eloquent model
- Easy to **retry file** of model
- Integrated JSON columns
- Integrated SoftDelete

## Installation

**Via Composer**
```bash 
composer require dongttfd/laravel-upload-model
```


## Basic Usage

### Use packages at your model:

```php

namespace App\Models;

use DongttFd\LaravelUploadModel\Contracts\UploadOnEloquentModel;
use DongttFd\LaravelUploadModel\Eloquent\UploadFileEloquent;

class User extends Model implements UploadOnEloquentModel
{
    use UploadFileEloquent;

    ...
}
```

### Implement

*Model create with uploaded file*

```php
...

// $file must is instance of Illuminate\Http\UploadedFile
$file = $request->file('avatar');

User::create(['avatar' => $file]);

```

*Model update with uploaded file*


```php
...

// $file must is instance of Illuminate\Http\UploadedFile
$file = $request->file('avatar');

$user->update(['avatar' => $file]);

```

*Easy to retries*


```php
...

// $file must is instance of Illuminate\Http\UploadedFile
$file = $request->file('avatar');

$user->update(['avatar' => $file]);

// path of file
$user->avatar;

// url of file
$user->avatar_url;

```

### Supported JSON columns

*Read [Override](##Override) before use JSON columns*

*With JSON/Array columns*


```php
// JSON object

$front = $request->file('front');
$back = $request->file('back');

$user->create([
    ...
    'card' => [
        'front' => $front,
        'back' => $back
    ]
]);

// retries path of file
$user->card['front'];
$user->card['back'];

// retries url of file
$user->card['front_url'];
$user->card['back_url'];

```

```php
// JSON Array
$photos = $request->file('photos'); // [UploadedFile, UploadedFile]

$post->create([
    ...
    'photos' => $photos
]);

// retries path of file
$post->photos; // ['<photo1-path>', <photo2-path>]

// retries url of file
$post->photos_url; // ['<photo1-url>', <photo2-url>]

```

```php
// JSON Array and Object combined
$variant = $request->only([
    'photos',  // UploadedFile[]
    'name', // String
    'key' // String
]);

$post->update([
    ...
    'variant' => $variant
]);

// retries path of file
$post->variant['photos']; // ['<photo1-path>', <photo2-path>]

// retries url of file
$post->variant['photos_url']; // ['<photo1-url>', <photo2-url>]

```

## Override

*We recommend to create a `BaseFileModel` class for file on your laravel project and implement `UploadOnEloquentModel` and extends that when you need.*

```php

<?php

namespace App\Models;

use DongttFd\LaravelUploadModel\Contracts\UploadOnEloquentModel;
use DongttFd\LaravelUploadModel\Eloquent\UploadFileEloquent;
use Illuminate\Database\Eloquent\Model;

class BaseFileModel extends Model implements UploadOnEloquentModel
{
    use UploadFileEloquent;
}


```

*Aside from you can extends `FileModel` from my source code*

```php
<?php

namespace App\Models;

use DongttFd\LaravelUploadModel\Eloquent\FileModel;

class User extends FileModel
{
}
```

### Override properties
**Specify the drive where you will save the file, default from your filesystem configuration (`config/filesystem.php`)**
```php
/**
 * Default save on disk (from keys of app/config/filesystem.php > disks)
 *
 * @var string
 */
protected $saveOnDisk = null;
```

**Specify the column where you will save the file, if that was JSON columns you must use `array dot`**

*avatar column is file*

```php
/**
 * Save file to avatar columns
 *
 * @var array
 */
protected $fileFields = ['avatar'];
```

*`card` column is object*

```php
/**
 * The attributes that should be cast.
 *
 * @var array
 */
protected $casts = [
    'card' => 'array',
];

/**
 * Save files to photos columns
 *
 * @var array
 */
protected $fileFields = [
    'card.front',
    'card.back'
];
```

*`photos` column is array*

```php
/**
 * The attributes that should be cast.
 *
 * @var array
 */
protected $casts = [
    'photos' => 'array',
];

/**
 * Save files to photos columns
 *
 * @var array
 */
protected $fileFields = ['photos.*'];
```

*`photos` is array in `variant` column*

```php
/**
 * The attributes that should be cast.
 *
 * @var array
 */
protected $casts = [
    'variant' => 'array',
];

/**
 * Save files to photos columns
 *
 * @var array
 */
protected $fileFields = ['variant.photos.*'];
```

**Specify the folders where you will save the file to disk**

```php
/**
 * Save path file to folder, format: ['<file-field>' => 'folder-name']
 *
 * @var array
 */
protected $fileFolders = [
    'avatar' => 'avatar',
    'card.front' => 'card-front',
    'variant.photos.*' => 'variant-photos',
];
```

**On / Off publish file when using `s3` driver**

```php
/**
 * Only s3 amazon: save with publish file
 *
 * @var bool
 */
protected $filePublish = false;
```

**Timeout of file access token when using `s3` file driver**

```php
/**
 * Only s3 amazon: expire time (minutes) off private file
 *
 * @var int
 */
protected $fileExpireIn = 5;
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


## Security

If you discover any security-related issues, please email [My Email](mailto:dongtt.fd@gmail.com) instead of using the issue tracker.


## Testing
```bash
composer test
```