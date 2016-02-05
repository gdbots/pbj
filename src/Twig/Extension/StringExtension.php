<?php

namespace Gdbots\Pbjc\Twig\Extension;

use Gdbots\Common\Util\StringUtils;

class StringExtension extends \Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('toCamelFromSlug', array($this, 'toCamelFromSlug')),
        );
    }

    /**
     * @param string $slug
     *
     * @return string
     */
    public function toCamelFromSlug($slug)
    {
        return StringUtils::toCamelFromSlug($slug);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'string';
    }
}
