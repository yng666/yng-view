<?php

namespace Yng\View\BladeXTemplate;

use Yng\Exception\FileNotFoundException;
use Yng\View\Exception\TemplateNotFoundException;

use InvalidArgumentException;
use League\Flysystem\InvalidRootException;

/**
 * blade模板编译器类
 */
class BladeCompiler_bak
{
    /**
     * 编译方法
     * @var array
     */
    protected $content;
    protected $path;
    protected $compilers = [
        // 'Statements',// 编译模板语句
        'Comments',// 编译模板注释
        'Echos',
        'Php',
        'Foreach',
        'If',
        'Unless',
        'For',
        'While',
        'Continue',
        'Break',
        'Include',
        'Each',
        'Yield',
        'Show',
        'Section',
        'Extends',
        'Stack',
        'Push',
        // 'Prepend',
        // 'Append',
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
    
    // /**
    //  * 自定义指令
    //  * @var array
    //  */
    // protected $customDirectives = [];
    
    /**
     * 定义输出格式的字符串
     * @var string
     */
    protected $echoFormat = 'e(%s)'; 
    
    // /**
    //  * 原始 PHP 代码正则表达式
    //  */
    // protected $rawPhpPattern = '/(?<!@){{\s*(.*?)\s*}}/s';
    
    // /**
    //  * 模板扩展实例
    //  * @var array
    //  */
    // protected $extensions = [];
    
    // /**
    //  * 模板指令实例
    //  * @var array
    //  */
    // protected $directives = [];

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
     * 循环变量名称
     * @var string $loopVariable
     */
    protected $loopVariable = '$__currentLoopData';

    /**
     * 循环计数器变量名称
     * @var string $loopCounterVariable
     */
    protected $loopCounterVariable = '$__iteration';

    /**
     * 环境变量名称
     * @var string $envVariable
     */
    protected $envVariable = '$__env';

    /**
     * 为空时循环变量名称
     * @var string $emptyVariable
     */
    protected $emptyVariable = '$__empty';

    /**
     * 定义将循环变量推入环境栈的 PHP 代码字符串
     * @var string $pushLoop
     */
    protected $pushLoop = '$__env->addLoop($__currentLoopData); $__env->incrementLoopIndices();';

    /**
     * 定义将循环变量从环境栈弹出的 PHP 代码字符串
     * @var string $popLoop
     */
    protected $popLoop = '$__env->popLoop(); $loop = $__env->getFirstLoop();';
    // 是不是还有个protected $escapedTags = ['{{{', '}}}'];

    /**
     * 编译模板内容
     */
    public function compile(string $value): string
    {
        $this->content = $value;

        // dd($value);

        foreach ($this->getCompilers() as $compiler) {
            $method = 'compile' . ucfirst($compiler);
            if (!method_exists($this, $method)) {
                throw new InvalidArgumentException("Directive '{$method}' does not exist.");
            }
            $this->$method($this->content);
        }

        dd($this->lastSectionStack);
        if (count($this->lastSectionStack) > 0) {
            throw new InvalidArgumentException('Unexpected missing section(s): "'.implode('", "', $this->lastSectionStack).'"');
        }

        return $this->content;
    }


    /**
     * 获取待编译的数组
     */
    public function getCompilers(): array
    {
        return $this->compilers;
    }

    /**
     * 处理模板文件中的 Blade 扩展标签的
     */
     /*protected function compileExtensions(string $value): string
    {
        foreach ($this->extensions as $name => $extension) {
            $pattern = "/(?<!\w)(\s*)@{$name}\s*(\(.*?\))?}(?!\w)/";

            if (is_callable($extension)) {
                $value = preg_replace_callback($pattern, function ($matches) use ($extension) {
                    return call_user_func_array($extension, array_slice($matches, 1));
                }, $value);
            } else {
                $value = preg_replace($pattern, $extension, $value);
            }
        }

        return $value;
    }*/

    /**
     * 编译包含标准语法或自定义指令的语句
     */
    /*protected function compileStatements(string $value): string
    {
        $tmp = implode('|', $this->customDirectives);
        
        $pattern = sprintf('/\B@(%s)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',$tmp);
        return preg_replace_callback($pattern, function ($match) {
            if (isset($match[3])) {
                $match[3] = $this->stripParentheses($match[3]);
            }
            return $this->compileStatement($match[1], isset($match[3]) ? $match[3] : '');
        }, $value);

    }*/

    /**
     * 编译指令
     */
    /*protected function compileStatement(string $name, string $expression): string
    {
        // if(empty($name)){
        //     return '';
        // }
        // if (isset($this->customDirectives[$name])) {
        //     return $this->customDirectives[$name]($expression);
        // }
    
        // throw new FileNotFoundException("Directive '{$name}' is not defined.");


        if (!method_exists($this, 'compile' . ucfirst($name))) {
            throw new InvalidArgumentException("Directive '{$name}' does not exist.");
        }
        return $this->{'compile' . ucfirst($name)}($expression);

    }*/

    /**
     * 将 Blade 模板中的注释语法 {{-- comment --}} 转换成 PHP 的注释语法 <?php \/* comment *\/ ?> 的方法
     * 这个方法的作用是让注释在 Blade 编译后不会出现在最终的 HTML 页面中
     */
    protected function compileComments(string $value): string
    {
        $pattern = '/{{--(.*?)--}}/s';

        return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
    }

    /**
     * 将 echo 语句编译为有效的PHP代码
     */
    protected function compileEchos(string $value): string
    {
        $pattern = sprintf('/(@)?(%s)(?=\s*(\(|\{|<|$))/s',implode('|', array_map('preg_quote', $this->escapedTags)));

        return preg_replace_callback($pattern, function ($match) {
            return $this->compileEchosCallback($match);
        }, $value);
    }

    /**
     * echo的回调方法
     */
    protected function compileEchosCallback(array $match): string
    {
        // 判断echo语句是否转义
        $escaped = isset($match[1]) && $match[1] !== '';
    
        // 获取echo语句的内容
        $content = $match[2];
    
        $content = trim($content);
    
        $isVariable = preg_match('/^\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $content);
    
        // 如果内容是一个变量，就返回它
        if ($isVariable) {
            return $escaped ? '<?php echo htmlspecialchars(' . $content . ', ENT_QUOTES, \'UTF-8\'); ?>' : '<?php echo ' . $content . '; ?>';
        }
    
        // 如果内容不是变量，用圆括号括起来并返回
        $wrappedContent = '(' . $content . ')';
    
        return $escaped ? '<?php echo htmlspecialchars(' . $wrappedContent . ', ENT_QUOTES, \'UTF-8\'); ?>' : '<?php echo ' . $wrappedContent . '; ?>';
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
    protected function compileFor2(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        $segments = preg_split('/\s*;\s*/', $expression, -1, PREG_SPLIT_NO_EMPTY);

        // $segments = explode(';', $expression);
        if (count($segments) !== 3) {
            throw new InvalidArgumentException("Invalid syntax for a \"for\" loop: {$expression}");
        }

        [$iteratee, $condition, $increment] = $segments;

        $initLoop = "{$iteratee};";

        $iterateLoop = "for ({$initLoop} {$condition}; {$increment}) {";

        return "<?php {$iterateLoop} ?>";
    }

    public function compileFor($expression)
    {
        $regex = '/\(\s*(\$\S+)\s*=\s*(\S+)\s*;\s*(\$\S+)\s*([<>=!]=?)\s*(\S+)\s*;\s*(\$\S+)\s*([-+]{2}|[-+]\s*\S+)\s*\)/';

        if (preg_match($regex, $expression, $matches)) {
            $variable = $matches[1];
            $start = $matches[2];
            $end = $matches[5];
            $step = $matches[6];

            $loop_index = $this->getLoopIndex();
            $output = "<?php for({$variable}={$start}; {$variable}{$matches[4]}{$end}; {$variable}{$step}): ?>";
            $output .= "<?php \${$loop_index} = ['index' => 0, 'iteration' => 1, 'first' => true, 'last' => false, 'odd' => false]; ?>";
            $output .= "<?php while({$variable}{$matches[4]}{$end}): ?>";
            $output .= "<?php \${$loop_index}['iteration']++; \${$loop_index}['index']++; \${$loop_index}['first'] = (\${$loop_index}['iteration'] == 2); ?>";
            $output .= "<?php if({$variable}{$matches[4]}{$end} && !((\${$loop_index}['iteration'] - 2) % 1)): ?>";
            $output .= "<?php \${$loop_index}['odd'] = true; ?>";
            $output .= "<?php else: \${$loop_index}['odd'] = false; endif; ?>";

            return $output;
        }

        throw new InvalidRootException("Invalid expression：{$expression}");
    }

    /**
     * 返回一个唯一的循环索引
     *
     * @return string
     */
    protected function getLoopIndex()
    {
        static $counter = 0;

        return '$__loop' . ++$counter;
    }


    protected function compileWhile(string $expression): string
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

    protected function compileContinue(string $expression): string
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

    protected function compileBreak(string $expression): string
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

    protected function compileInclude(string $expression): string
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

    protected function compileYield(string $expression): string
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

    protected function compileSection(string $expression): string
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

    protected function compileExtends(string $expression): string
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

    protected function compileStack(string $expression): string
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

    protected function compilePush(string $expression): string
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
     * 将“foreach”语句编译为有效的PHP代码
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileForeach0(string $value): string
    {
        $pattern = sprintf('/\B@(%s)\s*\((.*?)\)/s', implode('|', $this->loops));

        $res = preg_replace_callback($pattern, function ($match) {
            $segments = array_map('trim', explode(' as ', trim($match[2])));
            $iteratee = $segments[0];
            $iteration = count($segments) > 1 ? $segments[1] : 'value';
    
            $key = '$__currentLoopDataKey' . ++$this->loopCount;
            $value = '$__currentLoopDataValue' . $this->loopCount;
            $iterationKey = 'foreach' . $this->loopCount;
    
            $output = "<?php \$__currentLoopData = {$iteratee}; \$this->{$iterationKey} = ['iteration' => 0, 'total' => count(\$__currentLoopData)];";
            $output .= "if (\$this->{$iterationKey}['total'] > 0): foreach (\$__currentLoopData as {$key} => {$value}): ";
            $output .= "\$this->{$iterationKey}['iteration']++; ?>";
    
            $output = preg_replace("/$iteratee\.$iteration/", $value, $output);
            $output = str_replace($iteratee, $value, $output);
    
            $output .= "<?php endforeach; endif; ?>";
    
            return $output;
        }, $value);

        return $res;
    }

    protected function compileForeach2(string $expression): string
    {
        $expression = preg_replace('/\(\s*(.*)\s+as\s+(.*)\)/', '($1) as $2', $expression);
        [$iteratee, $iteration] = explode(' as ', $expression);
        $key = null;

        $output = "<?php \$__empty_{$this->loopCount} = true; \$__currentLoopData = {$iteratee}; \$__loopData = \$__currentLoopData instanceof Traversable ? \$__currentLoopData : (array) \$__currentLoopData; if (count(\$__loopData)):\n";
        if (strpos($iteration, ',') !== false) {
            [$key, $value] = explode(',', $iteration);
            $output .= "<?php foreach (\$__loopData as {$key} => $value):\n";
        } else {
            $value = trim($iteration);
            $output .= "<?php foreach (\$__loopData as $value):\n";
        }

        $output .= "    \$__empty_{$this->loopCount} = false;\n?>";
        $output .= $this->compileEchos("{$this->echoFormat}({$value})", true);
        $output .= "<?php endforeach; endif; ";
        $output .= "\$__env->incrementLoopIndices(); \$__env->popLoop(); \$__loopData = \$__currentLoopData; if (\$__empty_{$this->loopCount}): ?>";
        $this->loopCount++;
        return $output;
    }


    protected function compileForeach(string $expression): string
    {
        $expression = preg_replace('/\(\s*(.*)\s+as\s+(.*)\)/', '($1) as $2', $expression);
        [$iteratee, $iteration] = explode(' as ', $expression);
        [$key, $value] = array_pad(explode(',', $iteration), 2, null);
        // $empty = '$__empty';
        $iterationString = $key ? " as {$key} => {$value}" : " as {$value}";

        $output = "<?php {$this->emptyVariable}_{$this->loopCount} = true; {$this->loopVariable} = {$iteratee}; {$this->loopCounterVariable} = 0; if (!empty({$this->loopVariable})): {$this->pushLoop} ?>";
        $output .= "<?php foreach ({$this->loopVariable}{$iterationString}): {$this->loopCounterVariable}++ ?>";
        $output .= "<?php {$this->emptyVariable}_{$this->loopCount} = false; ?>";
        $output .= $this->compileEchos  ("{$this->echoFormat}({$value})");

        $this->lastSectionStack[] = [$this->emptyVariable, $this->loopCount];
        $this->rawStack[] = $this->emptyVariable;

        $this->loopCount++;

        return $output;
    }


    public function compilePhp($template)
    {
        // 正则表达式用于匹配@php指令及其包含的原生PHP代码
        $pattern = '/@php(.*?)\n/';
        // 匹配@php指令及其包含的原生PHP代码
        preg_match_all($pattern, $template, $matches);
        // 遍历匹配结果，编译原生PHP代码
        foreach ($matches[1] as $phpCode) {
            // 转义原生PHP代码中的引号和反斜杠
            $phpCode = addslashes($phpCode);
            // 将@php指令中的原生PHP代码转换成等效的PHP代码，并插入模板中
            $template = str_replace("@php{$phpCode}\n", "<?php {$phpCode} ?>", $template);
        }
        // 返回编译后的模板
        return $template;
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
     * 将 endforeach 语句编译为有效的PHP代码
     *
     * @param  string $expression 表达式
     * @return string
     */
    protected function compileEndforeach(): string
    {
        $this->loopCount--;

        return "<?php endforeach; endif; \$__env->popLoop(); \$__loopData = \$__currentLoopData; ?>";
    }


    protected function compileEndForeach0(): string
    {
        [$empty, $loopCount] = array_pop($this->lastSectionStack);
        $output = "<?php endforeach; ";
        $output .= "{$this->popLoop} ";
        $output .= "{$this->loopVariable} = \$__loopData; ";
        $output .= "if ({$empty}_{$loopCount}): ?>";
    
        return $output;
    }
    

    /**
     * 编译if指令
     *
     * @param string $expression 指令表达式
     * @return string 编译后的代码
     */
    protected function compileIf(string $expression): string
    {
        // 用正则表达式分离指令中的条件语句和else分支语句
        preg_match('/\((.*)\)(?:\s*)/', $expression, $matches);
        $condition = $matches[1];
        $output = "<?php if ($condition): ?>";

        // 将代码压入$lastSectionStack堆栈，以便在elseif和else指令中调用
        array_push($this->lastSectionStack, '__current_if');

        return $output;
    }

    /**
     * 编译elseif指令
     *
     * @param string $expression 指令表达式
     * @return string 编译后的代码
     */
    protected function compileElseif(string $expression): string
    {
        // 从$lastSectionStack堆栈中弹出最后一个指令
        $lastSection = array_pop($this->lastSectionStack);
        if ($lastSection !== '__current_if' && $lastSection !== '__current_elseif') {
            throw new InvalidArgumentException("Unexpected @elseif directive without matching @if.");
        }

        // 用正则表达式分离指令中的条件语句
        preg_match('/\((.*)\)(?:\s*)/', $expression, $matches);
        $condition = $matches[1];
        $output = "<?php elseif ($condition): ?>";

        // 将当前指令压入$lastSectionStack堆栈
        array_push($this->lastSectionStack, '__current_elseif');

        return $output;
    }

    /**
     * 编译else指令
     *
     * @return string 编译后的代码
     */
    protected function compileElse(): string
    {
        // 从$lastSectionStack堆栈中弹出最后一个指令
        $lastSection = array_pop($this->lastSectionStack);
        if ($lastSection !== '__current_if' && $lastSection !== '__current_elseif') {
            throw new InvalidArgumentException("Unexpected @else directive without matching @if.");
        }

        $output = "<?php else: ?>";

        // 将当前指令压入$lastSectionStack堆栈
        array_push($this->lastSectionStack, '__current_else');

        return $output;
    }

    /**
     * 编译endif指令
     *
     * @return string 编译后的代码
     */
    protected function compileEndif(): string
    {
        // 从$lastSectionStack堆栈中弹出最后一个指令
        $lastSection = array_pop($this->lastSectionStack);
        if ($lastSection !== '__current_if' && $lastSection !== '__current_elseif' && $lastSection !== '__current_else') {
            throw new InvalidArgumentException("Unexpected @endif directive without matching @if, @elseif or @else.");
        }

        return "<?php endif; ?>";
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

class BladeCompiler2
{
    protected $path;
    protected $content;
    protected $compiled_content;
    protected $directives = [];
    protected $echo_functions = [
        'e', 'trans', 'trans_choice', 'str', 'class_basename', 'studly_case', 'camel_case', 'kebab_case', 'snake_case'
    ];

    public function __construct($path)
    {
        $this->path = $path;
        $this->content = file_get_contents($path);
    }

    public function compile()
    {
        $this->compiled_content = $this->content;

        // Compile directives
        foreach ($this->directives as $name => $compiler) {
            $pattern = "/@{$name}(.+)/";
            $this->compiled_content = preg_replace_callback($pattern, $compiler, $this->compiled_content);
        }

        // Compile includes
        $this->compiled_content = preg_replace_callback('/@include\((.*?)\)/', [$this, 'compileInclude'], $this->compiled_content);

        // Compile variables
        $this->compiled_content = preg_replace_callback('/{{\s*(.*?)\s*}}/', [$this, 'compileVariable'], $this->compiled_content);

        // Compile echos
        $this->compiled_content = preg_replace_callback('/{!!\s*(.*?)\s*!!}/', [$this, 'compileEcho'], $this->compiled_content);

        return $this->compiled_content;
    }

    public function directive($name, $compiler)
    {
        $this->directives[$name] = $compiler;
    }

    protected function compileInclude($matches)
    {
        $filename = trim($matches[1], "'\"");
        $path = dirname($this->path) . '/' . $filename;
        $compiler = new BladeCompiler2($path);
        return $compiler->compile();
    }

    protected function compileVariable($matches)
    {
        return '<?php echo e(' . $this->compileExpression($matches[1]) . '); ?>';
    }

    protected function compileEcho($matches)
    {
        return '<?php echo ' . $this->compileExpression($matches[1]) . '; ?>';
    }

    protected function compileExpression($expression)
    {
        foreach ($this->echo_functions as $function) {
            if (preg_match('/^' . $function . '\((.*)\)$/', $expression, $matches)) {
                return $function . '(' . $this->compileExpression($matches[1]) . ')';
            }
        }

        return '$' . trim($expression);
    }
}

class BladeCompiler3
{
    protected $path;
    protected $content;
    protected $compiled_content;
    protected $echo_functions = [
        'e', 'trans', 'trans_choice', 'str', 'class_basename', 'studly_case', 'camel_case', 'kebab_case', 'snake_case'
    ];
    public function __construct($path)
    {
        $this->path = $path;
        $this->content = file_get_contents($path);
    }
    public function compile()
    {
        $this->compiled_content = $this->content;
         // Compile includes
        $this->compiled_content = preg_replace_callback('/@include\((.*?)\)/', [$this, 'compileInclude'], $this->compiled_content);
         // Compile variables
        $this->compiled_content = preg_replace_callback('/{{\s*(.*?)\s*}}/', [$this, 'compileVariable'], $this->compiled_content);
         // Compile echos
        $this->compiled_content = preg_replace_callback('/{!!\s*(.*?)\s*!!}/', [$this, 'compileEcho'], $this->compiled_content);
         // Compile conditionals
        $this->compiled_content = preg_replace_callback('/@if\((.*?)\)/', [$this, 'compileIf'], $this->compiled_content);
        $this->compiled_content = preg_replace('/@else/', '<?php else: ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@elseif\((.*?)\)/', '<?php elseif ($1): ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@endif/', '<?php endif; ?>', $this->compiled_content);
         // Compile loops
        $this->compiled_content = preg_replace_callback('/@foreach\((.*?)\)/', [$this, 'compileForeach'], $this->compiled_content);
        $this->compiled_content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $this->compiled_content);
         // Compile sections
        $this->compiled_content = preg_replace_callback('/@section\((.*?)\)/', [$this, 'compileSection'], $this->compiled_content);
        $this->compiled_content = preg_replace('/@endsection/', '<?php $__env->stopSection(); ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@show/', '<?php echo $__env->yieldSection(); ?>', $this->compiled_content);
         // Compile yield
        $this->compiled_content = preg_replace_callback('/@yield\((.*?)\)/', [$this, 'compileYield'], $this->compiled_content);
         // Compile parent
        $this->compiled_content = preg_replace_callback('/@parent/', [$this, 'compileParent'], $this->compiled_content);
        return $this->compiled_content;
    }
     protected function compileInclude($matches)
    {
        $filename = trim($matches[1], "'\"");
        $path = dirname($this->path) . '/' . $filename;
        $compiler = new BladeCompiler3($path);
        return $compiler->compile();
    }
     protected function compileVariable($matches)
    {
        return '<?php echo e(' . $this->compileExpression($matches[1]) . '); ?>';
    }
     protected function compileEcho($matches)
    {
        return '<?php echo ' . $this->compileExpression($matches[1]) . '; ?>';
    }
     protected function compileIf($matches)
    {
        return '<?php if (' . $this->compileExpression($matches[1]) . '): ?>';
    }
     protected function compileForeach($matches)
    {
        return '<?php foreach (' . $this->compileExpression($matches[1]) . ') : ?>';
    }
     protected function compileSection($matches)
    {
        $section_name = trim($matches[1], "'\"");
        return "<?php \$__env->startSection('$section_name'); ?>";
    }
     protected function compileYield($matches)
    {
        $section_name = trim($matches[1], "'\"");
        return "<?php echo \$__env->yieldContent('$section_name'); ?>";
    }
     protected function compileParent()
    {
        return '<?php echo $__env->parentContent(); ?>';
    }
     protected function compileExpression($expression)
    {
        foreach ($this->echo_functions as $function) {
            if (preg_match('/^' . $function . '\((.*)\)$/', $expression, $matches)) {
                return $function . '(' . $this->compileExpression($matches[1]) . ')';
            }
        }
         return '$' . trim($expression);
    }
}

class BladeCompiler4
{
    protected $path; // 文件路径
    protected $content; // 原始内容
    protected $compiled_content; // 编译后的内容
    protected $directives = []; // 指令
    protected $echo_functions = [
        'e', 
        'trans', 
        'trans_choice', 
        'str', 
        'class_basename', 
        'studly_case', 
        'camel_case', 
        'kebab_case', 
        'snake_case'
    ]; // 可以被编译为echo的函数
    public function __construct($path)
    {
        $this->path = $path;
        $this->content = file_get_contents($path);
    }
    public function compile()
    {
        // 编译指令
        foreach ($this->directives as $name => $compiler) {
            $pattern = "/@{$name}(.+)/";
            $this->compiled_content = preg_replace_callback($pattern, $compiler, $this->compiled_content);
        }
        // 编译include
        $this->compiled_content = preg_replace_callback('/@include\s*\(\s*[\'"](.+)[\'"]\s*\)/U', [$this, 'compileInclude'], $this->compiled_content);
        // 编译extends
        $this->compiled_content = preg_replace('/@extends\s*\(\s*[\'"](.+)[\'"]\s*\)/U', '<?php $__env->startSection(\'__parent\', $__env->make("$1", \'\')->__render()); ?>', $this->compiled_content);
        // 编译section和show
        $this->compiled_content = preg_replace('/@section\s*\(\s*[\'"](.+)[\'"]\s*\)/', '<?php $__env->startSection(\'$1\'); ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@endsection/', '<?php $__env->stopSection(); ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@show\s*\(\s*[\'"](.+)[\'"]\s*\)/', '<?php echo $__env->yieldContent(\'$1\'); ?>', $this->compiled_content);
        // 编译yield
        $this->compiled_content = preg_replace('/@yield\s*\(\s*[\'"](.+)[\'"]\s*\)/', '<?php echo $__env->yieldContent("$1"); ?>', $this->compiled_content);
        // 编译includeIf
        $this->compiled_content = preg_replace_callback('/@includeIf\s*\(\s*[\'"](.+)[\'"]\s*\)/U', [$this, 'compileIncludeIf'], $this->compiled_content);
        // 编译isset和empty
        $this->compiled_content = preg_replace('/@isset\s*\(\s*(.+?)\s*\)/', '<?php if(isset($$1)): ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@endisset/', '<?php endif; ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@empty\s*\(\s*(.+?)\s*\)/', '<?php if(empty($$1)): ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@endempty/', '<?php endif; ?>', $this->compiled_content);
        // 编译auth
        $this->compiled_content = preg_replace('/@auth\s*\(\s*([\'"])?(.+?)(?(1)\1)\s*\)/', '<?php if(auth()->check() && auth()->user()->$2): ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@endauth/', '<?php endif; ?>', $this->compiled_content);
        // 编译guest
        $this->compiled_content = preg_replace('/@guest/', '<?php if (auth()->guest()): ?>', $this->compiled_content);
        $this->compiled_content = preg_replace('/@endguest/', '<?php endif; ?>', $this->compiled_content);
        // 编译verbatim
        $this->compiled_content = preg_replace('/@verbatim(.*?)@endverbatim/s', '<?php echo "$1"; ?>', $this->compiled_content);
        // 编译variables
        $this->compiled_content = preg_replace_callback('/{{\s*(.+?)\s*}}/', [$this, 'compileVariable'], $this->compiled_content);
        // 编译echos
        $this->compiled_content = preg_replace_callback('/{!!\s*(.+?)\s*!!}/', [$this, 'compileEcho'], $this->compiled_content);
        return $this->compiled_content;
    }
    public function directive($name, $compiler)
    {
        $this->directives[$name] = $compiler;
    }
    protected function compileInclude($matches)
    {
        $filename = trim($matches[1], "'\"");
        $path = dirname($this->path) . '/' . $filename;
        $compiler = new BladeCompiler4($path);
        return $compiler->compile();
    }
    protected function compileIncludeIf($matches)
    {
        $filename = trim($matches[1], "'\"");
        $path = dirname($this->path) . '/' . $filename;
        $compiler = new BladeCompiler4($path);
        return '<?php if(file_exists("' . $path . '")): ?>' . $compiler->compile() . '<?php endif; ?>';
    }
    protected function compileVariable($matches)
    {
        return '<?php echo e(' . $this->compileExpression($matches[1]) . '); ?>';
    }
    protected function compileEcho($matches)
    {
        return '<?php echo ' . $this->compileExpression($matches[1]) . '; ?>';
    }
    protected function compileExpression($expression)
    {
        foreach ($this->echo_functions as $function) {
            if (preg_match('/^' . $function . '\((.*)\)$/', $expression, $matches)) {
                return $function . '(' . $this->compileExpression($matches[1]) . ')';
            }
        }
        return '$' . trim($expression);
    }
}