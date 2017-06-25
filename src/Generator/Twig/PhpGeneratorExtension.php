<?php

namespace Gdbots\Pbjc\Generator\Twig;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\SchemaDescriptor;

class PhpGeneratorExtension extends GeneratorExtension
{
    const LANGUAGE = 'php';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array_merge(parent::getFunctions(), [
            new \Twig_SimpleFunction('getClassName', [$this, 'getClassName']),
        ]);
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $majorRev
     * @param bool             $addBase
     * @param bool             $withAs
     * @param string           $postfix
     *
     * @return string
     */
    public function getClassName(SchemaDescriptor $schema, $majorRev = false, $addBase = false, $withAs = false, $postfix = null)
    {
        $className = StringUtils::toCamelFromSlug($schema->getId()->getMessage());

        if ($majorRev) {
            $className = sprintf(
                '%sV%d',
                StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                $schema->getId()->getVersion()->getMajor()
            );
        }

        $newClassName = $className;

        if ($addBase) {
            $newClassName = sprintf(
                '%s%s%s',
                StringUtils::toCamelFromSlug($schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($schema->getId()->getPackage()),
                $className
            );
        }

        if ($withAs) {
            $className = sprintf('%s%s as %s%s', $className, $postfix, $newClassName, $postfix);
        } else {
            $className = sprintf('%s%s', $newClassName, $postfix);
        }

        return $className;
    }
}
