<?php
namespace Hongfs\Cos\Plugin;

use League\Flysystem\Util;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class FolderHasPlugin implements PluginInterface
{
    protected $filesystem;

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMethod()
    {
        return 'folderHas';
    }

    public function handle($dirname = '')
    {
        $dirname = Util::normalizePath($dirname);

        return (bool) $this->filesystem->getAdapter()->folderHas($dirname);
    }
}