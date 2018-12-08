<?php
namespace Hongfs\Cos\Plugin;

use League\Flysystem\Util;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class GetUrlPlugin implements PluginInterface
{
    protected $filesystem;

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMethod()
    {
        return 'getUrl';
    }

    public function handle($path)
    {
        $path = Util::normalizePath($path);

        $this->filesystem->assertPresent($path);

        return (string) $this->filesystem->getAdapter()->getUrl($path);
    }
}