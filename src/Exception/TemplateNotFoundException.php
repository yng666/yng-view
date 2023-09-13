<?php
namespace Yng\View\Exception;

class TemplateNotFoundException extends \Exception
{
    protected string $file;
    protected int $line;

    public function __construct(string $message, string $file, int $lineNumber=0,\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->file = (string)$file;
        if($lineNumber){
            $this->line = (int)$lineNumber;
        }
        // dd($this);
    }

    /**
     * 获取模板文件
     * @access public
     * @return string
     */
    public function getPath(): string
    {
        return $this->file;
    }

    public function getLineNumber(): int
    {
        return $this->line;
    }

}
