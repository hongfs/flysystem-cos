<?php
namespace Hongfs\Cos\Plugin;

use League\Flysystem\Util;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class GetTemporaryUrlPlugin implements PluginInterface
{
    protected $filesystem;

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMethod()
    {
        return 'getTemporaryUrl';
    }

    public function handle($path, $expires = 3600, array $config = [])
    {
        $path = Util::normalizePath($path);

        $this->filesystem->assertPresent($path);

        return (string) $this->filesystem->getAdapter()->getTemporaryUrl($path, $expires, $config);
    }
}