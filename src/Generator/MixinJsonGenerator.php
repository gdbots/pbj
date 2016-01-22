<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\SlugUtils;

class MixinJsonGenerator extends Generator
{
    /** @var string */
    protected $template = 'mixin/Mixin.json.twig';

    /** @var string */
    protected $extension = '.json';

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
        return $this->isLatest ? 'latest' : $this->schema->getId()->getVersion();
    }
}
