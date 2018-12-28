<?php
namespace Hongfs\Cos\Tests;

use Hongfs\Cos\CosAdapter;
use Hongfs\Cos\Plugin\GetUrlPlugin;
use Hongfs\Cos\Plugin\GetTemporaryUrlPlugin;
use Hongfs\Cos\Plugin\FolderHasPlugin;
use Hongfs\Cos\Plugin\FolderCopyPlugin;
use Hongfs\Cos\Plugin\FolderRenamePlugin;
use League\Flysystem\Filesystem;
use League\Flysystem\Config;
use League\Flysystem\AdapterInterface;
use PHPUnit\Framework\TestCase;

class CosTest extends TestCase
{
    protected $driver;

    protected $filesystem;

    public function setUp()
    {
        $this->driver = new CosAdapter([
            'driver'        => 'cos',
            'secret_id'     => getenv('cos_secret_id'),
            'secret_key'    => getenv('cos_secret_key'),
            'bucket'        => getenv('cos_bucket'),
            'region'        => getenv('cos_region'),
        ]);

        $this->filesystem = new Filesystem($this->driver);
        $this->filesystem->addPlugin(new GetUrlPlugin);
        $this->filesystem->addPlugin(new GetTemporaryUrlPlugin);
        $this->filesystem->addPlugin(new FolderHasPlugin);
        $this->filesystem->addPlugin(new FolderCopyPlugin);
        $this->filesystem->addPlugin(new FolderRenamePlugin);
    }

    public function testDown()
    {
        $this->driver = null;
        $this->filesystem = null;
    }

    public function testHas()
    {
        $this->filesystem->write('1.txt', 'Test');
        $this->assertEquals(true, $this->filesystem->has('1.txt'));
        $this->assertEquals(false, $this->filesystem->has('1.log'));
        $this->filesystem->delete('1.txt');
    }

    public function testGetMetaData()
    {
        $this->filesystem->write('1.txt', 'Test');
        $this->assertInternalType('array', $this->filesystem->getMetadata('1.txt'));
        $this->assertInternalType('string', $this->filesystem->getMimetype('1.txt'));
        $this->assertInternalType('int', $this->filesystem->getTimestamp('1.txt'));
        $this->assertInternalType('int', $this->filesystem->getSize('1.txt'));
        $this->filesystem->delete('1.txt');
    }

    public function testGetUrl()
    {
        $this->filesystem->write('1.txt', 'Test');
        $this->assertInternalType('string', $this->filesystem->getUrl('1.txt'));
        $this->filesystem->delete('1.txt');
    }

    public function testGetTemporaryUrl()
    {
        $this->filesystem->write('1.txt', 'Test');
        $this->assertInternalType('string', $this->filesystem->getTemporaryUrl('1.txt'));
        $this->assertInternalType('string', $this->filesystem->getTemporaryUrl('1.txt', 3600));
        $this->assertInternalType('string', $this->filesystem->getTemporaryUrl('1.txt', 3600, [
            'internal' => true
        ]));
        $this->filesystem->delete('1.txt');
    }

    public function testRead()
    {
        $contents = 'Test';
        $filename = '1.txt';
        $this->assertTrue($this->filesystem->write($filename, $contents));
        $this->assertEquals($contents, $this->filesystem->read($filename));
        $this->filesystem->delete($filename);
    }

    public function testReadStream()
    {
        $contents = 'Test';
        $filename = '1.txt';
        $handle = tmpfile();
        fwrite($handle, $contents);
        $this->assertTrue($this->filesystem->writeStream($filename, $handle));
        is_resource($handle) && fclose($handle);
        $handle = $this->filesystem->readStream($filename);
        $this->assertInternalType('resource', $handle);
        $this->assertEquals($contents, stream_get_contents($handle));
        $this->filesystem->delete($filename);
    }

    public function testUpdate()
    {
        $contents = 'Update';
        $filename = '1.txt';
        $this->filesystem->write($filename, 'Test');
        $this->assertTrue($this->filesystem->update($filename, $contents));
        $this->assertEquals($contents, $this->filesystem->read($filename));
        $this->filesystem->delete($filename);
    }

    public function testUpdateStream()
    {
        $contents = 'Update';
        $filename = '1.txt';
        $handle = tmpfile();
        fwrite($handle, 'Test');
        $this->filesystem->writeStream($filename, $handle);
        $handle = tmpfile();
        fwrite($handle, $contents);
        $this->assertTrue($this->filesystem->updateStream($filename, $handle));
        is_resource($handle) && fclose($handle);
        $handle = $this->filesystem->readStream($filename);
        $this->assertInternalType('resource', $handle);
        $this->assertEquals($contents, stream_get_contents($handle));
        $this->filesystem->delete($filename);
    }

    public function testCopy()
    {
        $contents = 'Test';
        $form = '1.txt';
        $to = '2.txt';
        $this->filesystem->write($form, $contents);
        $this->filesystem->copy($form, $to);
        $this->assertTrue($this->filesystem->has($to));
        $this->assertEquals($contents, $this->filesystem->read($to));
        $this->filesystem->delete($form);
        $this->filesystem->delete($to);
    }

    public function testFolderCopy()
    {
        $contents = 'Test';
        $formDir = 'Test/';
        $toDir = 'To/';
        $form = '1.txt';
        $this->filesystem->createDir($formDir);
        $this->filesystem->write($formDir . $form, $contents);
        $this->assertTrue($this->filesystem->folderCopy($formDir, $toDir));
        $this->assertTrue($this->filesystem->folderHas($toDir));
        $this->assertEquals($contents, $this->filesystem->read($toDir . $form));
        $this->filesystem->deleteDir($formDir);
        $this->filesystem->deleteDir($toDir);
    }

    public function testRename()
    {
        $contents = 'Test';
        $form = '1.txt';
        $to = '2.txt';
        $this->filesystem->write($form, $contents);
        $this->filesystem->rename($form, $to);
        $this->assertTrue($this->filesystem->has($to));
        $this->assertFalse($this->filesystem->has($form));
        $this->filesystem->delete($to);
    }

    public function testFolderRename()
    {
        $contents = 'Test';
        $formDir = 'Test/';
        $toDir = 'To/';
        $form = '1.txt';
        $this->filesystem->createDir($formDir);
        $this->filesystem->write($formDir . $form, $contents);
        $this->assertTrue($this->filesystem->folderRename($formDir, $toDir));
        $this->assertTrue($this->filesystem->folderHas($toDir));
        $this->assertFalse($this->filesystem->folderHas($formDir));
        $this->assertEquals($contents, $this->filesystem->read($toDir . $form));
        $this->filesystem->deleteDir($toDir);
    }

    public function testGetListContents()
    {
        $contents = 'Test';
        $filename = '1.txt';
        $this->filesystem->write($filename, $contents);
        $this->assertCount(1, $this->filesystem->listContents('', true));
        $this->filesystem->delete($filename);
    }

    // public function testVisibility()
    // {
    //     $contents = 'Test';
    //     $filename = '1.txt';
    //     $visibility = AdapterInterface::VISIBILITY_PUBLIC;
    //     $this->filesystem->write($filename, $contents);
    //     $this->assertTrue($this->filesystem->setVisibility($filename, $visibility));
    //     $this->assertEquals($visibility, $this->filesystem->getVisibility($filename));
    //     $this->filesystem->delete($filename);
    // }

    public function testCreateDir()
    {
        $this->assertTrue($this->filesystem->createDir('path/to'));
    }

    public function testDelete()
    {
        $contents = 'Test';
        $filename = '1.txt';
        $this->filesystem->write($filename, $contents);
        $this->assertTrue($this->filesystem->delete($filename));
    }

    public function testDeleteDir()
    {
        $this->filesystem->write('path/to/1.txt', 'Test');
        $this->filesystem->write('path/to/another/1.txt', 'Test');
        $this->assertTrue($this->filesystem->deleteDir('path/to'));
        $this->assertFalse($this->filesystem->has('path/to/1.txt'));
        $this->assertFalse($this->filesystem->has('path/to/another/1.txt'));
    }
}