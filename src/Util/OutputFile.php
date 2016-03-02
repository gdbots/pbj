<?php

namespace Gdbots\Pbjc\Util;

class OutputFile
{
    /** @var string */
    protected $file;

    /** @var string */
    protected $content;

    /**
     * Constructor.
     *
     * @param string $file    The file name
     * @param string $content The file content
     */
    public function __construct($file, $content = null)
    {
        $this->file = $file;
        $this->content = $content;
    }

    /**
     * Returns the file name.
     *
     * @return string the file name
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns the contents of the file.
     *
     * @return string the contents of the file
     */
    public function getContents()
    {
        return $this->content ?: $this->content = file_get_contents($this->file);
    }
}
