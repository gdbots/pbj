<?php

namespace Gdbots\Pbjc\Util;

class OutputFile
{
    /** @var string */
    protected $file;

    /** @var string */
    protected $contents;

    /**
     * @param string $file     The file name
     * @param string $contents The file content
     */
    public function __construct($file, $contents)
    {
        $this->file = $file;
        $this->contents = $contents;
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
        return $this->contents;
    }
}
