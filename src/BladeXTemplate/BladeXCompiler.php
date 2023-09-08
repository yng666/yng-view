<?php

namespace Yng\View\BladeXTemplate;

use Yng\Exception\FileNotFoundException;
use Yng\View\Exception\TemplateNotFoundException;
use InvalidArgumentException;

/**
 * bladex模板编译器类
 */
class BladeXCompiler
{

    protected $content;
    protected $path;

    /**
     * 编译方法
     * @var array
     */
    protected $compilers = [
        'Extends',
        'Section',
        // 'Statements',// 编译模板语句
        'Comments',// 编译模板注释
        'Echo',
        'If',
        'Else',
        'ElseIf',
        'EndIf',
        'Foreach',
        'EndForeach',
        'Php',
        // 'Unless',
        'For',
        'EndFor',
        'While',
        'EndWhile',
        'Continue',
        'Break',
        // 'Include',
        // 'Stop',
        // 'Lang',
    ];

    /**
     * 存储原始标签的开启和结束标签
     * @var array $rawTags
     */
    protected $rawTags = ['{!!', '!!}'];
    
    /**
     * 存储转义标签的开启和结束标签
     * @var array
     */
    protected $escapedTags = ['{{', '}}'];

    /**
     * 父模板
     * @var string
     */
    protected $template = []; 

    /**
     * 循环指令的计数器，用于生成唯一的变量名
     * @var int
     */
    protected $loopCount = 0;

    /**
     * 已编译的指令缓存 
     * @var array
     */
    protected $compilerCache = [];

    /**
     * 存储已编译模板的底部内容
     * @var array $footer
     */
    protected $footer = [];

    /**
     * 存储最后一个 section 的名称
     * @var array $lastSectionStack
     */
    protected $lastSectionStack = [];

    /**
     * 存储未编译的原始模板段落
     * @var array $rawStack
     */
    protected $rawStack = [];

    /**
     * 用于在 @forelse 中跟踪当前循环的计数器
     * @var int $forelseCounter
     */
    protected $forelseCounter = 0;

    /**
     * 存储编译过程中出现的异常
     * @var array $compilationExceptions
     */
    protected $compilationExceptions = [];

    /**
     * 编译模板内容
     */
    public function compile(string $filePath,string $value): string
    {
        $this->content = $value;
        $this->path = $filePath;

        $compiler_arr = $this->getCompilers();
        foreach ($compiler_arr as $compiler) {
            $method = 'compile' . ucfirst($compiler);
            if (!method_exists($this, $method)) {
                throw new TemplateNotFoundException("Directive '{$method}' does not exist.",$method);
            }
            $this->$method($this->content);
        }

        return $this->content;
    }


    /**
     * 编译模板内容
     * 不编译 extends section标签
     */
    private function exCompile(string $filePath,string $value): string
    {
        $this->content = $value;
        $this->path = $filePath;

        $compiler_arr = $this->getCompilers();
        foreach ($compiler_arr as $compiler) {
            if(in_array($compiler,['Extends','Section'])){
                continue;
            }
            $method = 'compile' . ucfirst($compiler);
            if (!method_exists($this, $method)) {
                throw new TemplateNotFoundException("Directive '{$method}' does not exist.",$method);
            }
            $this->$method($this->content);
        }

        return $this->content;
    }

    /**
     * 获取待编译的数组
     */
    protected function getCompilers(): array
    {
        return $this->compilers;
    }

    /**
     * 将 Blade 模板中的注释语法 {{-- comment --}} 转换成 PHP 的注释语法 <?php \/* comment *\/ ?> 的方法
     * 这个方法的作用是让注释在 Blade 编译后不会出现在最终的 HTML 页面中
     */
    protected function compileComments(string $value): string
    {
        $pattern = '/{{--(.*?)--}}/s';
        $this->content = preg_replace($pattern, '<?php /*\\n$1\\n*/ ?>', $value);
        return $this->content;
    }

    /**
     * 将 @echo 语句编译为有效的PHP代码
     */
    protected function compileEcho(string $expression): string
    {
        list($raw_start_tags,$raw_end_tags) = $this->rawTags;
        list($start_tags,$end_tags) = $this->escapedTags;
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s/s',preg_quote($start_tags, '/'),preg_quote($end_tags, '/'));

        $pattern_str = '/(?<!\\w)(%s)(\\s*(.+?)\\s*)(%s)(?!\\w)/s';

        // 匹配原始标签正则
        $pattern = sprintf($pattern_str,preg_quote($raw_start_tags),preg_quote($raw_end_tags));
        
        // 匹配转义后的正则
        $escaped_pattern = sprintf($pattern_str,preg_quote($start_tags),preg_quote($end_tags));
    
        $expression = preg_replace($escaped_pattern, '<?php echo htmlspecialchars($2, ENT_QUOTES, \'UTF-8\', false); ?>', $expression);

        $this->content = preg_replace_callback($pattern, function ($matches) {
            return sprintf('<?php echo %s; ?>', $this->compileEchosCallback($matches));
        }, $expression);
        
        // dd($this->content);
        return $this->content;
    }

    /**
     * echo的回调方法
     */
    protected function compileEchosCallback($matches)
    {
        // 过滤为空的情况
        if(!$matches){
            return $matches;
        }

        // 调用trim函数删除表达式两侧的空格
        $expression = trim($matches[2]);


        // 判断是否是变量
        if (preg_match('/^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $expression, $matches)) {
            // 如果是变量，直接返回变量名
            return $matches[0];
        }
    
        // 判断是否是函数
        if (preg_match('/^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\((.*)\)$/', $expression, $matches)) {
            // 如果是函数，调用compileEchosCallback递归编译函数参数，并返回函数调用代码
            $functionName = $matches[1];
            
            // $params = array_map([$this, 'compileEchosCallback'], explode(',', $matches[2]));
            // dd($functionName,$matches);
            $params = trim($matches[2]);
            return sprintf('%s(%s)', $functionName, $params);
        }
    
        // 其他情况，将表达式作为字符串返回
        return sprintf("'%s'", addslashes($expression));
    }

    /**
     * 编译 @if 指令
     *
     * @param string $expression 指令表达式
     * @return string 编译后的代码
     */
    protected function compileIf(string $expression): string
    {
        $content_tmp = $expression;
        // 用正则表达式分离指令中的条件语句和else分支语句
        $pattern = '/@if\((.*?)\)/i';
        if(preg_match_all($pattern, $content_tmp, $matches)){
            $conditions = $matches[1];
            // 将代码压入$lastSectionStack堆栈，以便在elseif和else指令中调用
            $this->lastSectionStack['if'] = $conditions;
            foreach($this->lastSectionStack['if'] as $key => $condition){
                $output = sprintf('<?php if (%s): ?>', $condition);
                $content_tmp = preg_replace($pattern,$output,$content_tmp,1);
                unset($this->lastSectionStack['if'][$key]);
            }
            if(empty($this->lastSectionStack['if'])){
                unset($this->lastSectionStack['if']);
            }
            $this->content = $content_tmp;
        }
        unset($content_tmp);
        unset($expression);
        return $this->content;
    }

    /**
     * 编译 @elseif 指令
     *
     * @param string $expression 指令表达式
     * @return string 编译后的代码
     */
    protected function compileElseIf(string $expression): string
    {
        $content_tmp = $expression;
        // 用正则表达式分离指令中的条件语句
        $pattern = '/@elseif\((.*?)\)/i';
        if(preg_match_all($pattern, $expression, $matches)){
            $conditions = $matches[1];
           
            // 将代码压入$lastSectionStack堆栈，以便在elseif和else指令中调用
            $this->lastSectionStack['elseif'] = $conditions;


            foreach($this->lastSectionStack['elseif'] as $key => $condition){
                $output = sprintf('<?php elseif (%s): ?>', $condition);
                $content_tmp = preg_replace($pattern,$output,$content_tmp,1);
                unset($this->lastSectionStack['elseif'][$key]);
            }

            if(empty($this->lastSectionStack['elseif'])){
                unset($this->lastSectionStack['elseif']);
            }
            $this->content = $content_tmp;
        }elseif(preg_match_all('/(@elseif[a-z]*)/i', $content_tmp, $errorMatches)){
            $errorExpression = implode(',', $errorMatches[1]);
            throw new TemplateNotFoundException("Invalid expression：{$errorExpression}",$errorExpression);
        }
        unset($content_tmp);
        unset($expression);
        return $this->content;
    }

    /**
     * 编译 @else 指令
     *
     * @return string 编译后的代码
     */
    protected function compileElse(string $expression): string
    {
        // 用正则表达式分离指令中的条件语句
        if(preg_match('/@else\b/i', $expression, $matches)){
            $output = "<?php else : ?>";
            $this->content = preg_replace('/@else\b/i',$output,$expression);
        }

        return $this->content;
    }

    /**
     * 编译 @endif 指令
     *
     * @return string 编译后的代码
     */
    protected function compileEndIf(string $expression): string
    {
        $this->content = $expression;
        // 用正则表达式分离指令中的条件语句
        $output = "<?php endif; ?>";
        if(preg_match_all('/(@endif.*)/i', $expression, $matches)){
            foreach($matches[1] as $key => $val){
                if(preg_match('/@endif\b/i', $val, $mth)){
                    $this->content = preg_replace('/@endif\b/i',$output,$expression);
                }
            }
        }
        return $this->content;
    }


    /**
     * 将 @foreach 语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileForeach(string $expression): string
    {
        $this->content = $expression;
        $content_tmp = $expression;
        $pattern = '/@foreach\s*\(\s*(.*?)\s*\)/ims';
        $cond_pattern = '/^(\S+)\s+(?:as\s+(\S+)\s*(=>\s*(\S+))?)?$/i';
        // 获取循环变量、键名和值名
        if(preg_match_all($pattern, $content_tmp, $matches)){

            $conditions = $matches[1];

            $this->lastSectionStack['foreach'] = $conditions;
            foreach($this->lastSectionStack['foreach'] as $kk => $condition){
                if(preg_match($cond_pattern, $condition, $con_arr)){
                    $data   = $this->stripParentheses($con_arr[1]);
                    $key    = isset($con_arr[4]) ? $this->stripParentheses($con_arr[2]) : '$key';
                    $value  = isset($con_arr[4]) ? $this->stripParentheses($con_arr[4]) : $this->stripParentheses($con_arr[2]);
                    $output = sprintf('<?php foreach(%s as %s => %s): ?>', $data,$key,$value);

                    $content_tmp = preg_replace($pattern,$output,$content_tmp,1);
                    unset($this->lastSectionStack['foreach'][$kk]);
                }else{
                    throw new TemplateNotFoundException("Invalid expression: {$condition}" . $condition,$this->path);
                }
            }
            if(empty($this->lastSectionStack['foreach'])){
                unset($this->lastSectionStack['foreach']);
            }
            $this->content = $content_tmp;
        }

        return $this->content;
    }


    /**
     * 将 @endforeach 语句编译为有效的PHP代码
     *
     * @param  string $expression 表达式
     * @return string
     */
    protected function compileEndforeach($expression): string
    {
        $this->content = $expression;
        // 用正则表达式分离指令中的条件语句
        $output = "<?php endforeach; ?>";
        if(preg_match('/@endforeach\b/i', $expression, $matches)){
            $this->content = preg_replace('/@endforeach\b/i',$output,$expression);
        }
        return $this->content;
    }


    /**
     * 将 @php...@endphp 语句编译为有效的PHP代码
     * @param  string $expression 表达式
     * @return string
     */
    protected function compilePhp(string $expression):string
    {
        $this->content = $expression;
        $pattern = '/@php\s*(.*?)\s*@endphp/si';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return sprintf('<?php %s ?>', $matches[1]);
        }, $expression);
        return $this->content;
    }

    /**
     * 将 @extends 语句编译为有效的PHP代码
     * @param  string $expression 表达式
     * @return string
     */
    protected function compileExtends(string $expression):string
    {
        $this->content = $expression;
        $content_tmp = $expression;
        $pattern = '/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i';
        // 找到模板中的 extends 标签
        if (preg_match_all($pattern, $expression, $matches)) {
            
            $parentTemplatePath = $matches[1];
            
            // dd($matches);
            $this->lastSectionStack['extends'] = $parentTemplatePath;
            foreach($this->lastSectionStack['extends'] as $key => $val){
                $filePath = $this->getFilePath($val);
                if($filePath === false){
                    $lineNumber = $this->findLineNumberForToken(T_EXTENDS);
                    throw new TemplateNotFoundException("Invalid path: {$parentTemplatePath};Only dots and slashes are supported.",$this->path,$lineNumber);
                }

                if(!is_file($filePath)){
                    $lineNumber = $this->findLineNumberForToken(T_EXTENDS);
                    throw new TemplateNotFoundException("The path is not a valid file: {$parentTemplatePath}",$this->path,$lineNumber);
                }

                if(!file_exists($filePath)){
                    $lineNumber = $this->findLineNumberForToken(T_EXTENDS);
                    throw new TemplateNotFoundException("File not found: {$parentTemplatePath}",$this->path,$lineNumber);
                }
                // 读取父视图模板内容，并将其与子视图内容合并
                $parentTemplateContent = file_get_contents($filePath);
                // 解析父视图标签
                $parentTemplateContent = $this->exCompile($filePath,$parentTemplateContent);
                $content_tmp = preg_replace($pattern,$parentTemplateContent,$content_tmp,1);

                unset($this->lastSectionStack['extends'][$key]);
            }

            if(empty($this->lastSectionStack['extends'])){
                unset($this->lastSectionStack['extends']);
            }

            $this->content = $content_tmp;
            unset($content_tmp);
            unset($expression);
        }
    
        return $this->content;
    }



    /**
     * 判断路径方式，只支持 . 和 / 写法
     * @param string $path 视图路径
     */
    protected function getFilePath(string $path)
    {
        $filePath = null;
        // 判断路径方式，只支持 . 和 / 写法
        if (stripos($path, '.') !== false) {
            $filePath = resource_path('views') . str_replace('.', DIRECTORY_SEPARATOR, $path) . '.blade.php';
        } elseif (stripos($path, '/') !== false && count(explode('/', $path))) {
            $filePath = resource_path('views') . str_replace('/', DIRECTORY_SEPARATOR, $path) . '.blade.php';
        } else {
            $filePath = false;
        }
        return $filePath;
    }

    // 查找指定 token 的行号
    protected function findLineNumberForToken($token)
    {
        $tokens = token_get_all($this->content);
        // dd($tokens);
        $lineNumber = 1;
        foreach ($tokens as $t) {
            if (is_array($t) && $t[0] == $token) {
                $lineNumber = $t[2];
                break;
            } elseif (is_string($t) && $t == "\n") {
                $lineNumber++;
            }
        }

        return $lineNumber;
    }


    /**
     * 编译模板中的 @section 和 @endsection 标记
     * 替换@yield里的内容
     *
     * @param string $template 原始模板
     * @return string 编译后的模板
     */
    protected function compileSection(string $expression):string
    {
        $pattern = '/@section\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endsection/si';

        $content_tmp = $expression;
        $this->content = $content_tmp;
        if(preg_match_all($pattern, $content_tmp, $matches)){
            // dd($matches);
            $this->lastSectionStack['section_title']   = $matches[1];
            $this->lastSectionStack['section_content'] = $matches[2];

            foreach($this->lastSectionStack['section_content'] as $key => $val){
                $section_title = $this->lastSectionStack['section_title'][$key];
                // 匹配模板对应的yield内容
                $start = '<!-- '.$section_title.' start -->';
                $end   = '<!-- '.$section_title.' end -->';
                $not_replace = [
                    'title',//浏览器标题
                    'seo_title',//seo标题
                    'seo_author',//seo作者
                    'seo_keywords',//seo关键词
                    'seo_description',//seo描述
                    'og_title',//开放协议:标题
                    'og_image',//开放协议:图片地址
                    'og_release_date',//开放协议:发表时间
                    'og_description',//开放协议:描述
                    'og_author',//开放协议:作者
                ];
                if(in_array($section_title,$not_replace)){
                    $start = '';
                    $end = '';
                }

                $output = $start . $val . $end;
                if(preg_match("/@yield\(\'{$section_title}\'\)/i",$content_tmp,$match)){
                    // dd($match,$output);
                    $content_tmp = preg_replace("/@yield\(\'{$section_title}\'\)/i",$output,$content_tmp,1);
                    $content_tmp = preg_replace($pattern,'',$content_tmp,1);
                    unset($this->lastSectionStack['section_title'][$key]);
                    unset($this->lastSectionStack['section_content'][$key]);
                }else{
                    // dd($output,123);
                    $content_tmp = preg_replace($pattern,'',$content_tmp,1);
                }
                // dd($content_tmp,110);
                // $content_tmp = preg_replace($pattern,$output,$content_tmp,1);
            }

            if(empty($this->lastSectionStack['section_title'])){
                unset($this->lastSectionStack['section_title']);
            }
            if(empty($this->lastSectionStack['section_content'])){
                unset($this->lastSectionStack['section_content']);
            }

            // dd($content_tmp,'section');
            $this->content = $content_tmp;
        }

        $pattern = '/@yield\s*\(\s*([\'"])(.+?)\s*([\'"])\s*\)/i';
        if(preg_match_all($pattern, $content_tmp, $matches)){
            $this->lastSectionStack['yield'] = $matches[2];
            $not_replace = [
                'title',//浏览器标题
                'seo_title',//seo标题
                'seo_author',//seo作者
                'seo_keywords',//seo关键词
                'seo_description',//seo描述
                'og_title',//开放协议:标题
                'og_image',//开放协议:图片地址
                'og_release_date',//开放协议:发表时间
                'og_description',//开放协议:描述
                'og_author',//开放协议:作者
            ];
            foreach($this->lastSectionStack['yield'] as $key => $val){
                // 匹配模板对应的yield内容
                $content_tmp = preg_replace($pattern,'',$content_tmp,1);
                unset($this->lastSectionStack['yield'][$key]);
            }
            if(empty($this->lastSectionStack['yield'])){
                unset($this->lastSectionStack['yield']);
            }
            $this->content = $content_tmp;
        }

        // dd($this->content,11);
        return $this->content; // 返回编译后的模板
    }


    /**
     * 将 @include 语句编译为有效的PHP代码
     * @param  string $expression 表达式
     * @return string
     */
    public function compileInclude(string $expression):string
    {
        $this->content = $expression;
        $pattern = '/@include\s*(\(.*?\))/s';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $args = $this->stripParentheses($matches[1]);
            return sprintf('<?php echo $this->getIncludeContents(%s); ?>', $args);
        }, $expression);
        return $this->content;
    }

    /**
     * 引入文件并检测文件是否存在
     */
    protected function getIncludeContents($file)
    {
        if (file_exists($file)) {
            ob_start();
            include $file;
            return ob_get_clean();
        } else {
            throw new FileNotFoundException(sprintf('File %s not found', $file));
        }
    }

    /**
     * 编译刀片循环，如@for， @foreach，@while，@forelse等.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileLoops(string $value): string
    {
        foreach (['forelse', 'for', 'while', 'foreach'] as $structure) {
            $pattern = sprintf('/@%s(.*?)@end%s/s', $structure, $structure);

            $value = preg_replace_callback($pattern, function ($matches) use ($structure) {
                return $this->{"compile{$structure}"}($matches[1]);
            }, $value);
        }

        return $value;
    }

    /**
     * 将“forelse”语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileForelse(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        preg_match('/\( *(.*) +as *(.*) *\)/', $expression, $matches);

        $iteratee = trim($matches[1]);

        $iteration = trim($matches[2]);

        $initLoop = "\$__currentLoopData = {$iteratee}; \$__loop = (is_array(\$__currentLoopData) || \$__currentLoopData instanceof Countable) ? count(\$__currentLoopData) : 0;";

        $emptyLoop = "<?php if (\$__loop == 0): ?>";

        $iterateLoop = "<?php \$__currentLoopData = {$iteratee}; \$__iterationKey = 0; \$__loop = (is_array(\$__currentLoopData) || \$__currentLoopData instanceof Countable) ? count(\$__currentLoopData) : 0; foreach (\$__currentLoopData as {$iteration}): \$__env->incrementLoopIndices(); \$__iterationKey++; ?>";

        $iterateLoop .= $emptyLoop;

        $iterateLoop .= "<?php endforeach; ?>";

        $iterateLoop .= "<?php \$__env->popLoop(); \$__env->flush(); ?>";

        $iterateLoop .= "<?php endif; ?>";

        return "<?php {$initLoop} {$iterateLoop} ?>";
    }

    /**
     * 将“for”语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    public function compileFor(string $expression):string
    {
        $content_tmp = $expression;
        $this->content = $content_tmp;
        $pattern = '/@for\((\$\w+)\s*=\s*(.*);\s*(\$\w+)\s*(>|<|>=|<=)\s*(.*);\s*(\$\w+)?\s*(\+{2}|-{2})?\)/i';
        if (preg_match_all($pattern, $content_tmp, $matches)) {

            // dd($matches);
            $variable1 = $matches[1];
            $start = $matches[2];
            $variable2 = $matches[3];
            $condition = $matches[4];
            $end = $matches[5];
            $variable3 = $matches[6];
            $step = $matches[7];
            $output = [];

            foreach($matches[0] as $key => $val){
                $output[$key] = '';
            }

            foreach($output as $key => $val){
                $variable1_str  = $variable1[$key];
                $variable2_str  = $variable2[$key];
                $variable3_str  = $variable3[$key];
                $start_str     = $start[$key];
                $condition_str = $condition[$key];
                $end_str       = $end[$key];
                $step_str      = $step[$key];
                $output[$key] .= '<?php for('.trim($variable1_str).' = '.$start_str.';'.$variable2_str.' '.$condition_str.' '.$end_str.';'.$variable3_str.$step_str.'): ?>';

            }

            foreach($output as $key => $value){
                $content_tmp = preg_replace($pattern,$value,$content_tmp,1);
            }

            $this->content = $content_tmp;
        }
        return $this->content;
    }

    /**
     * 将 endfor 语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    public function compileEndFor(string $expression):string
    {
        $this->content = $expression;
        // 用正则表达式分离指令中的条件语句
        $output = "<?php endfor; ?>";
        if(preg_match('/@endfor\b/i', $expression, $matches)){
            $this->content = preg_replace('/@endfor\b/i',$output,$expression);
        }
        return $this->content;
    }

    /**
     * 将 while 语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileWhile(string $expression): string
    {
        $pattern = '/@while\((.+)\)/';
        $this->content = $content_tmp = $expression;
        if (preg_match_all($pattern, $content_tmp, $matches)) {
            $conditions = $matches[1];
            $this->lastSectionStack['while'] = $conditions;
            foreach($this->lastSectionStack['while'] as $key => $condition){
                $output = sprintf('<?php while(%s): ?>',$condition);
                $content_tmp = preg_replace($pattern,$output,$content_tmp);
                unset($this->lastSectionStack['while'][$key]);
            }

            if(empty($this->lastSectionStack['while'])){
                unset($this->lastSectionStack['while']);
            }
            $this->content = $content_tmp;
        }

        return $this->content;
    }


    /**
     * 将 endwhile 语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndWhile(string $expression):string
    {
        $this->content = $expression;
        // 用正则表达式分离指令中的条件语句
        $output = "<?php endwhile; ?>";
        if(preg_match('/@endwhile\b/i', $expression, $matches)){
            $this->content = preg_replace('/@endwhile\b/i',$output,$expression);
        }
        return $this->content;
    }

    /**
     * 将 continue 语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileContinue(string $expression): string
    {
        $pattern = '/@continue\s*\((.*?)\)/s';
        $content_tmp = $expression;
        $this->content = $content_tmp;
        if(preg_match_all($pattern, $content_tmp, $matches)){
            $conditions = $matches[1];
            $this->lastSectionStack['continue'] = $conditions;

            foreach($this->lastSectionStack['continue'] as $key => $condition){
                $output = sprintf('<?php if(%s): continue; endif; ?>',$condition);
                $content_tmp = preg_replace($pattern,$output,$content_tmp);
                unset($this->lastSectionStack['continue'][$key]);
            }

            if(empty($this->lastSectionStack['continue'])){
                unset($this->lastSectionStack['continue']);
            }
            $this->content = $content_tmp;
        }
        return $this->content;
    }

    /**
     * 将 break 语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileBreak(string $expression): string
    {
        $pattern = '/@break\s*\((.*?)\)/s';
        $content_tmp = $expression;
        $this->content = $content_tmp;
        if(preg_match_all($pattern, $content_tmp, $matches)){
            $conditions = $matches[1];
            $this->lastSectionStack['break'] = $conditions;

            foreach($this->lastSectionStack['break'] as $key => $condition){
                $output = sprintf('<?php if(%s): break; endif; ?>',$condition);
                $content_tmp = preg_replace($pattern,$output,$content_tmp);
                unset($this->lastSectionStack['break'][$key]);
            }

            if(empty($this->lastSectionStack['break'])){
                unset($this->lastSectionStack['break']);
            }
            $this->content = $content_tmp;
        }
        return $this->content;
    }


    protected function compileEach(string $expression): string
    {
        // $regex = '/@for\s*\(\s*(\$\S+)\s*=\s*(\S+)\s*;\s*(\$\S+)\s*([<>=!]=?)\s*(\S+)\s*;\s*(\S+)\s*\)/';
        // if (preg_match($regex, $statement, $matches)) {
        //     $variable = $matches[1];
        //     $start = $matches[2];
        //     $end = $matches[5];
        //     $step = $matches[6];
        //     // ...
        // }
        return $expression;
    }


    protected function compileShow(string $expression): string
    {
        // $regex = '/@for\s*\(\s*(\$\S+)\s*=\s*(\S+)\s*;\s*(\$\S+)\s*([<>=!]=?)\s*(\S+)\s*;\s*(\S+)\s*\)/';
        // if (preg_match($regex, $statement, $matches)) {
        //     $variable = $matches[1];
        //     $start = $matches[2];
        //     $end = $matches[5];
        //     $step = $matches[6];
        //     // ...
        // }
        return $expression;
    }






     /**
     * 将 @unless 指令编译为等效的 PHP 代码
     *
     * @param string $expression
     * @return string
     */
    public function compileUnless($expression)
    {
        // 将表达式中的 $ 符号去掉，因为 PHP8 中不再需要加 $
        $variable = preg_replace('/\s*\$\s*/', '', $expression);
        // 校验表达式是否合法
        if (empty(trim($variable))) {
            throw new InvalidArgumentException("Invalid expression: {$expression}");
        }
        // 构建输出的 PHP 代码
        return "<?php if (!($variable)) { ?>";
    }

     /**
     * 将 @endunless 指令编译为等效的 PHP 代码
     *
     * @return string
     */
    public function compileEndUnless()
    {
        /*
        // 弹出分支栈顶部元素
        $branch = array_pop($this->branchStack);
        // 如果栈为空，说明没有与 @unless 对应的 @endunless
        if (empty($this->branchStack)) {
            throw new InvalidArgumentException("未匹配的 @endunless 指令");
        }
        // 如果当前分支为 false，说明需要添加 else 分支
        if (!$branch) {
            return "<?php else: ?>";
        }*/
        // 否则，直接返回 endif 结构
        return '<?php endif; ?>';
    }


    

    /**
     * 接收一个字符串参数，用于从给定的表达式中去除括号
     * 如果表达式包含括号，则去除括号后返回，否则返回原表达式。在处理函数或方法调用时，我们需要去除括号，因为我们在处理调用时不需要括号。
     * 
     * @param  string  $expression
     * @return string
     */
    protected function stripParentheses($expression)
    {
        if (strpos($expression, '(') !== false && strrpos($expression, ')') === strlen($expression) - 1) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }

}
