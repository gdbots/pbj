<?php

namespace Gdbots\Pbjc\Twig\Extension;

use Gdbots\Common\Util\StringUtils;

class StringExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('className', array($this, 'className')),
            new \Twig_SimpleFunction('indentString', array($this, 'indentString')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('reduceSpaces', array($this, 'reduceSpaces')),
            new \Twig_SimpleFilter('toCamelFromSlug', array($this, 'toCamelFromSlug')),
        );
    }

    /**
     * @param mixed $object
     *
     * @return string
     */
    public function className($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    /**
     * @param string $str
     * @param int    $spaces
     *
     * @return string|null
     */
    public function indentString($str, $spaces)
    {
        if (!$str) {
            return;
        }

        if ($spaces === 0) {
            return $str;
        }

        $lines = explode("\n", $str);

        foreach ($lines as &$line) {
            $line = sprintf('%\' '.$spaces.'s%s', '', $line);
        }

        return implode("\n", $lines);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function reduceSpaces($str)
    {
        return trim(preg_replace('/\s+/', ' ', $str));
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'string';
    }
}
