<?php

namespace Gdbots\Pbjc\Twig\Extension;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

class SchemaExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('getClassName', array($this, 'getClassName')),
            new \Twig_SimpleFunction('hasOtherMajorRev', array($this, 'hasOtherMajorRev')),
        );
    }

    /**
     * @param SchemaDescriptor $schema
     * @param bool             $majorRev
     * @param string           $baseClassName
     * @param bool             $withAs
     *
     * @return string
     */
    public function getClassName(SchemaDescriptor $schema, $majorRev = false, $baseClassName = null, $withAs = false)
    {
        $className = StringUtils::toCamelFromSlug($schema->getId()->getMessage());

        if ($majorRev) {
            $className = sprintf(
                '%sV%d',
                StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                $schema->getId()->getVersion()->getMajor()
            );
        }

        if ($baseClassName == $className) {
            $classNameBase = sprintf(
                '%s%s%s',
                StringUtils::toCamelFromSlug($schema->getId()->getVendor()),
                StringUtils::toCamelFromSlug($schema->getId()->getPackage()),
                $className
            );

            if ($withAs) {
                $className = sprintf('%s as %s', $className, $classNameBase);
            } else {
                $className = $classNameBase;
            }
        }

        return $className;
    }

    /**
     * @param SchemaDescriptor $schema
     *
     * @return bool
     */
    public function hasOtherMajorRev(SchemaDescriptor $schema)
    {
        return SchemaStore::hasOtherSchemaMajorRev($schema->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'schema';
    }
}
