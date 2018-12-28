<?php

namespace Hongfs\Cos;

use Hongfs\Cos\Exceptions\FolderExistsException;
use Hongfs\Cos\Exceptions\FolderNotFoundException;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Qcloud\Cos\Client;

class CosAdapter extends AbstractAdapter
{
    /**
     * @const  VISIBILITY_PRIVATE  私人可见性
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * @const  VISIBILITY_PUBLIC  公共可见性
     */
    const VISIBILITY_PUBLIC = 'public-read';

    /**
     * Cos.
     *
     * @var Qcloud\Cos\Client
     */
    protected $cos;

    /**
     * Bucket.
     *
     * @var string
     */
    protected $bucket;

    /**
     * 地域
     *
     * @var bool
     */
    protected $region;

    /**
     * ACL.
     *
     * @var string
     */
    protected $acl = self::VISIBILITY_PRIVATE;

    /**
     * 内网.
     *
     * @var bool
     */
    protected $internal = false;

    /**
     * SSL.
     *
     * @var bool
     */
    protected $ssl = false;

    /**
     * CDN.
     *
     * @var string
     */
    protected $cdn = null;

    /**
     * CDN Token.
     *
     * @var string
     */
    protected $cdnToken = null;

    /**
     * disable asserts.
     *
     * @var bool
     */
    protected $disable_asserts = false;

    /**
     * 获取域名.
     *
     * @return string
     */
    protected function getDomain()
    {
        if (!$this->internal && $this->cdn) {
            return $this->cdn;
        }

        return $this->getDefaultDomain();
    }

    /**
     * 获取默认域名.
     *
     * @return string
     */
    protected function getDefaultDomain()
    {
        return $this->bucket.'.cos.'.$this->region.'.myqcloud.com';
    }

    /**
     * 获取Url前缀
     *
     * @return string
     */
    protected function getUrlPrefix()
    {
        return ($this->ssl ? 'https' : 'http').'://'.$this->getDomain().'/';
    }

    /**
     * 格式化时间.
     *
     * @param string $time
     *
     * @return int
     */
    protected function normalizeTime($time)
    {
        if (is_int($time)) {
            return strlen($time) == 10 ? (int) $time : time() + $time;
        } elseif (is_string($time)) {
            return strtotime($time);
        } else {
            return time();
        }
    }

    /**
     * 格式化路径.
     *
     * @param string $time
     * @param bool   $isDir
     *
     * @return string
     */
    protected function normalizePath($path, $isDir = true)
    {
        if ($path == '') {
            return $path;
        }

        if ($isDir) {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
        }

        return $path;
    }

    public function __construct($config)
    {
        try {
            $this->cos = new Client([
                'region'            => $config['region'],
                'credentials'       => [
                    'secretId'      => $config['secret_id'],
                    'secretKey'     => $config['secret_key'],
                    'token'         => isset($config['token']) ? $config['token'] : null,
                ],
                'timeout'           => isset($config['timeout']) ? $config['timeout'] : 3600,
                'connect_timeout'   => isset($config['connect_timeout']) ? $config['connect_timeout'] : 3600,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }

        if (!isset($config['bucket'])) {
            throw new \Exception('Bucket can not be empty');
        }

        $this->region = $config['region'];
        $this->bucket = $config['bucket'];
        if (isset($config['acl'])) {
            $this->acl = $config['acl'];
        }
        if (isset($config['internal'])) {
            $this->internal = (bool) $config['internal'];
        }
        if (isset($config['ssl'])) {
            $this->ssl = (bool) $config['ssl'];
        }
        if (isset($config['cdn']) && filter_var($config['cdn'], FILTER_VALIDATE_DOMAIN)) {
            $this->cdn = $config['cdn'];
        }
        if (isset($config['cdn_token'])) {
            $this->cdnToken = $config['cdn_token'];
        }
    }

    /**
     * 写入文件.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return bool
     */
    public function write($path, $contents, Config $config)
    {
        try {
            $this->cos->Upload($this->bucket, $path, $contents);

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * 写入文件流
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config
     *
     * @return bool
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, $resource, $config);
    }

    /**
     * 更新文件.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config
     *
     * @return bool
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * 更新文件流
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config
     *
     * @return bool
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * 获取文件.
     *
     * @param string $path
     *
     * @return array
     */
    public function read($path)
    {
        return [
            'contents' => file_get_contents($this->getTemporaryUrl($path)),
        ];
    }

    /**
     * 获取文件流
     *
     * @param string $path
     *
     * @return array
     */
    public function readStream($path)
    {
        return [
            'stream' => fopen($this->getTemporaryUrl($path), 'r'),
        ];
    }

    /**
     * 判断文件是否存在.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has($path)
    {
        return $this->getMetadata($path) !== false;
    }

    /**
     * 判断文件夹是否存在.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function folderHas($dirname)
    {
        try {
            $result = $this->cos->listObjects([
                'Bucket'    => $this->bucket,
                'MaxKeys'   => 1,
                'Prefix'    => $dirname.'/',
            ]);

            return isset($result['Contents']);
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * 重命名文件.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function rename($from, $to)
    {
        return $this->copy($from, $to) && $this->delete($from);
    }

    /**
     * 重命名文件夹.
     *
     * @param string $dirname
     * @param string $newDirname
     *
     * @return bool
     */
    public function folderRename($dirname, $newDirname)
    {
        return $this->folderCopy($dirname, $newDirname) && $this->deleteDir($dirname);
    }

    /**
     * 复制文件.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copy($from, $to)
    {
        try {
            $this->cos->Copy(
                $this->bucket,
                $to,
                $this->getDefaultDomain().'/'.$from
            );

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * 复制文件夹.
     *
     * @param string $dirname
     * @param string $newDirname
     *
     * @return bool
     */
    public function folderCopy($dirname, $newDirname)
    {
        $list = $this->listContents($dirname, true);

        try {
            $dirnameLen = strlen($dirname);

            foreach ($list as $item) {
                $path = substr($item['path'], $dirnameLen);

                $this->copy($dirname.$path, $newDirname.$path);
            }

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * 获取文件MineType.
     *
     * @param string $path
     *
     * @return array
     */
    public function getMimetype($path)
    {
        $mimeType = $this->getMetadata($path, 'ContentType', '');

        return [
            'mimetype' => explode(';', $mimeType)[0],
        ];
    }

    /**
     * 获取文件最后修改时间戳.
     *
     * @param string $path
     *
     * @return array
     */
    public function getTimestamp($path)
    {
        $date = $this->getMetadata($path, 'LastModified', 0);

        return [
            'timestamp' => $date ? strtotime($date) : $date,
        ];
    }

    /**
     * 获取文件大小.
     *
     * @param string $path
     *
     * @return array
     */
    public function getSize($path)
    {
        return [
            'size' => $this->getMetadata($path, 'ContentLength', 0),
        ];
    }

    /**
     * 获取文件Head信息.
     *
     * 可以通过设置$key获取指定参数
     *
     * @param string      $path
     * @param string|null $key
     * @param string|null $default
     *
     * @return array|string|bool
     */
    public function getMetadata($path, $key = null, $default = null)
    {
        try {
            $result = $this->cos->headObject([
                'Bucket'    => $this->bucket,
                'Key'       => $path,
            ]);
        } catch (\Exception $e) {
            return false;
        }

        if ($result) {
            if (!$key) {
                return $result->toArray();
            }

            return isset($result[$key]) ? $result[$key] : $default;
        }

        return $key ? $default : false;
    }

    /**
     * 删除文件.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        try {
            $this->cos->deleteObject([
                'Bucket'    => $this->bucket,
                'Key'       => $path,
            ]);

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * 删除文件夹.
     *
     * @param string $path
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $result = $this->listContents($dirname, true);

        foreach ($result as $item) {
            if (!$this->delete($item['path'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 创建文件夹.
     *
     * COS本身不支持文件夹，所以通过一个0字节来模拟
     *
     * @param string $path
     * @param  Config
     *
     * @return bool
     */
    public function createDir($path, Config $config)
    {
        return (bool) $this->write($this->normalizePath($path), '', $config);
    }

    /**
     * 获取列表.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array|string|bool
     */
    public function listContents($directory = '', $recursive = false)
    {
        $directory = $this->normalizePath($directory);

        try {
            $list = [];

            $marker = '';

            while (true) {
                $result = $this->cos->listObjects([
                    'Bucket'    => $this->bucket,
                    'Marker'    => $marker,
                    'MaxKeys'   => 1000,
                    'Prefix'    => $directory,
                ]);

                foreach ($result['Contents'] as $item) {
                    $name = $item['Key'];

                    // 如果不需要遍历子目录，判断文件夹分隔符第一次出现的位置
                    // 是不是$name长度-1，不是的话就跳过
                    if (!$recursive) {
                        $i = strrpos($name, '/');

                        if ($i && $i != strlen($name) - 1) {
                            continue;
                        }
                    }

                    // 判断是不是根目录文件
                    if (strpos($name, '/') === false) {
                        $list[$name] = 'file';
                        continue;
                    }

                    // 模拟文件夹
                    // 将路径按照分隔符分割开，然后从左向右开始合并起来
                    $dirArr = explode('/', $name);

                    $dirLen = count($dirArr);

                    foreach ($dirArr as $key => $value) {
                        if ($value === '' || $dirLen == $key + 1) {
                            continue;
                        }

                        $dirName = (string) implode('/', array_slice($dirArr, 0, $key + 1));

                        if (!isset($list[$dirName])) {
                            $list[$dirName.'/'] = 'dir';
                        }
                    }

                    // 最后1个字符不是分隔符则是文件
                    if (substr($name, -1) !== '/') {
                        $list[$name] = 'file';
                    }
                }

                if (!$result['IsTruncated']) {
                    break;
                }

                $marker = $result['NextMarker'];
            }

            // 对最后的文件列表转换为Filesystem可以识别的
            $data = [];

            foreach ($list as $name => $type) {
                $data[] = [
                    'path' => $name,
                    'type' => $type,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 设置文件可见性(暂不可用).
     *
     * @param string $path
     * @param string $visibility
     *
     * @return bool
     */
    public function setVisibility($path, $visibility)
    {
        return true;

        try {
            $result = $this->cos->PutObjectAcl([
                'Bucket'    => $this->bucket,
                'Key'       => $path,
                'ACL'       => $visibility == AdapterInterface::VISIBILITY_PUBLIC
                                ? self::VISIBILITY_PUBLIC
                                : self::VISIBILITY_PRIVATE,
            ]);

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * 获取文件可见性(暂不可用).
     *
     * @param string $path
     *
     * @return array|bool
     */
    public function getVisibility($path)
    {
        $visibility =   $this->acl == self::VISIBILITY_PRIVATE
                            ? self::VISIBILITY_PRIVATE
                            : self::VISIBILITY_PUBLIC;

        return [
            'visibility' => $visibility
        ];

        try {
            $result = $this->cos->getObjectAcl([
                'Bucket'    => $this->bucket,
                'Key'       => $path,
            ]);
        } catch (\Exception $e) {
            return false;
        }

        $visibility = $this->acl != self::VISIBILITY_PRIVATE;

        foreach ($result['Grants'] as $item) {
            if (isset($item['Grantee']) && isset($item['Grantee']['URI']) && $item['Grantee']['URI'] == 'http://cam.qcloud.com/groups/global/AllUsers') {
                $visibility = $item['Permission'] == 'READ';
                break;
            }
        }

        return [
            'visibility' => $visibility
                             ? AdapterInterface::VISIBILITY_PUBLIC
                             : AdapterInterface::VISIBILITY_PRIVATE,
        ];
    }

    /**
     * 获取链接.
     *
     * 判断文件是否为公共文件，是返回固定链接，反之返回临时链接
     *
     * @param string $path
     *
     * @return string|bool
     */
    public function getUrl($path)
    {
        $acl = $this->getVisibility($path);

        if ($acl === false) {
            return false;
        }

        if ($acl['visibility'] == AdapterInterface::VISIBILITY_PUBLIC) {
            return $this->getUrlPrefix().$path;
        }

        return $this->getTemporaryUrl($path);
    }

    /**
     * 获取临时链接.
     *
     * @param string     $path
     * @param string|int $expires
     * @param array      $options
     *
     * @return string
     */
    public function getTemporaryUrl($path, $expires = 3600, $options = [])
    {
        // 获取格式化的过期时间
        $expires = $this->normalizeTime($expires);

        if (isset($options['internal'])) {
            // 判断是否通过内网
            $this->internal = (bool) $options['internal'];
            unset($options['internal']);
        }

        if (!$this->internal && $this->cdn) {
            $url = $this->getUrlPrefix().$path;

            if ($this->cdnToken) {
                $sign = md5($this->cdnToken.'/'.$path.$expires);

                return $url.'?sign='.$sign.'&t='.$expires;
            }

            return $url;
        }

        return $this->cos->getObjectUrl($this->bucket, $path, date('Y-m-d H:i:s e', $expires), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function assertFolderPresent($dirname)
    {
        if ($this->disable_asserts === false && !$this->folderHas($dirname)) {
            throw new FolderNotFoundException($dirname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertFolderAbsent($dirname)
    {
        if ($this->disable_asserts === false && $this->folderHas($dirname)) {
            throw new FolderExistsException($dirname);
        }
    }
}
