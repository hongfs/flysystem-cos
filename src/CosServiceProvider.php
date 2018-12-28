<?php

namespace Hongfs\Cos;

use Hongfs\Cos\Plugin\FolderCopyPlugin;
use Hongfs\Cos\Plugin\FolderHasPlugin;
use Hongfs\Cos\Plugin\FolderRenamePlugin;
use Hongfs\Cos\Plugin\GetTemporaryUrlPlugin;
use Hongfs\Cos\Plugin\GetUrlPlugin;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class CosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        \Storage::extend('cos', function ($app, $config) {
            $filesystem = new Filesystem(new CosAdapter($config), $config);
            $filesystem->addPlugin(new GetUrlPlugin());
            $filesystem->addPlugin(new GetTemporaryUrlPlugin());
            $filesystem->addPlugin(new FolderHasPlugin());
            $filesystem->addPlugin(new FolderCopyPlugin());
            $filesystem->addPlugin(new FolderRenamePlugin());

            return $filesystem;
        });
    }

    /**
     * Register any application services.
     */
    public function register()
    {
    }
}
