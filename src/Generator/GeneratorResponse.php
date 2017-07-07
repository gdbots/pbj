<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Pbjc\Util\OutputFile;

final class GeneratorResponse
{
    /** @var array */
    protected $files = [];

    /**
     * @param OutputFile $file
     *
     * @return self
     */
    public function addFile(OutputFile $file)
    {
        $this->files[$file->getFile()] = $file;
        return $this;
    }

    /**
     * @return OutputFile[]
     */
    public function getFiles()
    {
        return $this->files ?: [];
    }
}
