<?php

namespace Gdbots\Pbjc\Generator\Twig;

use Gdbots\Common\Util\SlugUtils;
use Gdbots\Common\Util\StringUtils;

class StringExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('className', [$this, 'className']),
            new \Twig_SimpleFunction('indentString', [$this, 'indentString']),
            new \Twig_SimpleFunction('dump', [$this, 'dump']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('reduceSpaces', [$this, 'reduceSpaces']),
            new \Twig_SimpleFilter('toSlugFromCamel', [$this, 'toSlugFromCamel']),
            new \Twig_SimpleFilter('toCamelFromSlug', [$this, 'toCamelFromSlug']),
            new \Twig_SimpleFilter('slugify', [$this, 'slugify']),
        ];
    }

    /**
     * @param mixed $object
     *
     * @return string
     */
    public function dump($object)
    {
        return print_r($object, true);
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
            $line = sprintf('%\' ' . $spaces . 's%s', '', $line);
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
     * @param string $camel
     *
     * @return string
     */
    public function toSlugFromCamel($camel)
    {
        return StringUtils::toSlugFromCamel($camel);
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
     * @param string $string
     *
     * @return string
     */
    public function slugify($string)
    {
        return SlugUtils::create($string);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'string';
    }
}
