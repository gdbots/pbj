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
     *
     * @return bool
     */
    public function getClassName(SchemaDescriptor $schema, $majorRev = false)
    {
        if ($majorRev) {
            return sprintf(
                '%sV%d',
                StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                $schema->getId()->getVersion()->getMajor()
            );
        }

        return StringUtils::toCamelFromSlug($schema->getId()->getMessage());
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
