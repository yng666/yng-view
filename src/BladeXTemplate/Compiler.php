<?php

namespace Yng\View\BladeXTemplate;

use Yng\File\Filesystem;
use InvalidArgumentException;

/**
 * 抽象编译类
 */
abstract class Compiler
{
    /**
     * 文件实例
     *
     * @var \Yng\File\Filesystem
     */
    protected $files;

    /**
     * 获取已编译视图的缓存路径
     *
     * @var string
     */
    protected $cachePath;

    /**
     * 创建一个新的编译器实例
     *
     * @param  \Yng\File\Filesystem  $files
     * @param  string  $cachePath
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Filesystem $files, $cachePath)
    {
        if (!$cachePath) {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }

        $this->files = $files;
        $this->cachePath = $cachePath;
    }

    /**
     * 获取视图的编译版本的路径
     *
     * @param  string  $path 渲染文件路径
     * @return string
     */
    public function getCompiledPath($path)
    {
        return $this->cachePath.sha1(env('APP_NAME','YNG').md5_file($path)).'.php';
    }

    /**
     * 判断缓存文件是否过期
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        if(empty($path)){
            return false;
        }
        $compiled = $path;

        // 如果已编译的文件不存在，我们将指示该视图已过期，以便重新编译。否则，我们将验证视图的最后一次修改小于已编译视图的修改时间
        if (!$this->files->exists($compiled)) {
            return false;
        }

        return $this->files->lastModified($path) >= $this->files->lastModified($compiled);
    }

    /**
     * 如果需要，创建编译文件目录
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureCompiledDirectoryExists($path)
    {
        if (! $this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }
}
