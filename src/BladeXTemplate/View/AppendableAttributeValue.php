<?php
declare(strict_types = 1);

namespace Yng\View\BladeXTemplate\View;

class AppendableAttributeValue
{
    /**
     * The attribute value.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new appendable attribute value.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the string value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}