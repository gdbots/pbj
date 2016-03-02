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
     * @return this
     */
    public function addFile(OutputFile $file)
    {
        $this->fields[$file->getFile()] = $file;
        return $this;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->fields ?: [];
    }
}
