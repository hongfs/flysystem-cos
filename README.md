# COS for Filesystem

## 安装

```shell
$ composer require hongfs/flysystem-cos
```

## 使用

```php
use Hongfs\Cos\CosAdapter;
use League\Flysystem\Filesystem;

$config = [
    'driver'        => 'cos',
    'secret_id'     => '<secret_id>',
    'secret_key'    => '<secret_key>',
    'bucket'        => '<bucket-appid>',
    'region'        => '<region>',
];

$filesystem = new Filesystem(new CosAdapter($config));

bool $flysystem->has('1.txt');

bool $flysystem->write('1.txt', 'Test');

bool $flysystem->writeStream('1.txt', fopen('1.txt', 'r'));

bool $flysystem->update('1.txt', fopen('1.txt', 'r'));

bool $flysystem->updateStream('1.txt', fopen('1.txt', 'r'));

string $flysystem->read('1.txt');

resource $flysystem->readStream('1.txt');

bool $flysystem->rename('1.txt', '2.txt');

bool $flysystem->copy('1.txt', '2.txt');

bool $flysystem->createDir('Test/');

bool $flysystem->delete('1.txt');

bool $flysystem->deleteDir('Test/');

array $flysystem->getMetadata('1.txt');

int $flysystem->getSize('1.txt');

string $flysystem->getMimetype('1.txt');

int $flysystem->getTimestamp('1.txt');

array $flysystem->listContents();

string $flysystem->setVisibility('1.txt', 'public');

string $flysystem->getVisibility('1.txt');
```

## 扩展

```php
use Hongfs\Cos\Plugin\GetUrlPlugin;
use Hongfs\Cos\Plugin\GetTemporaryUrlPlugin;
use Hongfs\Cos\Plugin\FolderHasPlugin;
use Hongfs\Cos\Plugin\FolderCopyPlugin;
use Hongfs\Cos\Plugin\FolderRenamePlugin;

$filesystem->addPlugin(new GetUrlPlugin);
$filesystem->addPlugin(new GetTemporaryUrlPlugin);
$filesystem->addPlugin(new FolderHasPlugin);
$filesystem->addPlugin(new FolderCopyPlugin);
$filesystem->addPlugin(new FolderRenamePlugin);

string $flysystem->getUrl('1.txt');

string $flysystem->getTemporaryUrl('1.txt');

bool $flysystem->folderHas('Test/');

bool $flysystem->folderCopy('Test/', 'Test2/');

bool $flysystem->folderRename('Test/', 'Test2/');
```

## Laravel

`config/filesystems.php`

```php
'disks' => [
    // ...
    'cos' => [
        'driver'        => 'cos',
        'secret_id'     => '<secret_id>',
        'secret_key'    => '<secret_key>',
        'bucket'        => '<bucket-appid>',
        'region'        => '<region>',
        'ssl'           => true,
    ]
]
```

```php
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('cos');

$disk->get('1.txt');

// https://laravel.com/docs/5.7/filesystem
```

## License

MIT