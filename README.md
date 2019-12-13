# COS for Filesystem

## 安装

```shell
$ composer require hongfs/flysystem-cos:dev-master
```

## 使用

> 由于COS ACL策略有1000条限制，暂时取消getVisibility, setVisibility设置

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

// string $flysystem->setVisibility('1.txt', 'public');

// string $flysystem->getVisibility('1.txt');
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

// https://laravel.com/docs/6.x/filesystem
```

## License

MIT
