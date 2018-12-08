<?php
namespace Hongfs\Cos\Plugin;

use League\Flysystem\Util;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class FolderCopyPlugin implements PluginInterface
{
    protected $filesystem;

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMethod()
    {
        return 'folderCopy';
    }

    public function handle($dirname, $newDirname)
    {
        $dirname = Util::normalizePath($dirname);
        $newDirname = Util::normalizePath($newDirname);

        $this->filesystem->getAdapter()->assertFolderPresent($dirname);
        $this->filesystem->getAdapter()->assertFolderAbsent($newDirname);

        return (bool) $this->filesystem->getAdapter()->folderCopy($dirname, $newDirname);
    }
}