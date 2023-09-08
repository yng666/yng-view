<?php
declare(strict_types=1);
namespace Yng\View\BladeXTemplate;

use Yng\App;
use Yng\View\Exception\TemplateNotFoundException;

use Yng\File\Filesystem;
/**
 * bladex渲染模板
 */
class BladeXTemplate extends Compiler
{
    /**
     * 模板配置参数
     * @var array
     */
    public $config = [
        'view_path'  => '', // 模板路径
        'view_depr'  => DIRECTORY_SEPARATOR,
        'cache_path' => '',
    ];

    protected $view_path = '';
    protected $cache_path = '';

    // 存放编译文件对应的md5路径值
    protected $includeFile = [];

    /**
     * 编译类
     */
    protected $compile;
    protected $sections = []; // 存储所有 section 的内容
    protected $extends  = []; // 存储所有 extend 的内容
    protected $sectionStack = []; // 存储当前正在处理的 section 名称

    public function __construct(App $app,$config)
    {
        $this->config    = array_merge($this->config, $config);
        $this->cachePath = $config['cache_path'];
        $this->files     = $app->make(Filesystem::class);
        $this->compile   = $app->make(BladeXCompiler::class);
    }

    /**
     * 渲染
     * @param string $view 模板路径
     * @param array $data 渲染数据
     */
    public function render($view, $data = [])
    {
        // 判读路径方式,只支持.和/写法
        if(stripos($view,'.') !== false){
            $filePath = $this->config['view_path'] . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.blade.php';
        }elseif((stripos($view,'/') !== false) && count(explode('/',$view))){
            $filePath = $this->config['view_path'] . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.blade.php';

        }else{
            throw new TemplateNotFoundException("Invalid path: {$view};Only dots and slashes are supported.",$view);
        }
        $cacheFile = $this->getCompiledPath($filePath);
        // dd($cacheFile);

        if (!file_exists($filePath)) {
            throw new TemplateNotFoundException("View file not found: {$view}",$view);
        }

        // 编译文件过期重新编译,或者是更新文件
        if ($this->isExpired($cacheFile) === false) {
            $contents = file_get_contents($filePath);
            $compiled = $this->compile->compile($filePath,$contents);
            file_put_contents($cacheFile, $compiled);
        }

        // 把 $data 数组中的键值作为变量名，值作为变量值，导入到当前作用域
        extract($data);
        // 把内容放到缓冲区
        ob_start();
        ob_implicit_flush(false);

        include $cacheFile;
        $content = ob_get_clean();

        exit($content);
    }


    /**
     * 注册自定义扩展
     */
    public function extend(string $name, callable $callback): void
    {
        $this->compile->registerExtension($name, $callback);
    }

    /**
     * 注册自定义指令
     */
    public function directive(string $name, callable $callback): void
    {
        $this->compile->registerDirective($name, $callback);
    }
}
