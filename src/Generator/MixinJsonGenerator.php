<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\SlugUtils;

class MixinJsonGenerator extends Generator
{
    /** @var string */
    protected $template = 'mixin/Mixin.json.twig';

    /** @var string */
    protected $extension = '.json';

    /** @var string */
    protected $prefix = '-mixin';

    /**
     * {@inheritdoc}
     */
    public function isOnlyLatest()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetFilename()
    {
        $filename = $this->schema->getId();

        if ($this->isLatest) {
            $filename = str_replace($this->schema->getId()->getVersion(), 'latest', $filename);
        }

        return $filename;
    }
}
