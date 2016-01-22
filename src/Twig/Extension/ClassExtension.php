<?php

namespace Gdbots\Pbjc\Twig\Extension;

class ClassExtension extends \Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('class', array($this, 'getClass')),
        );
    }

    /**
     * @param mixed $object
     *
     * @return string
     */
    public function getClass($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'class';
    }
}
