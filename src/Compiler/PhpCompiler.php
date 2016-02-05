<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Schema;
use Gdbots\Pbjc\Generator\PhpGenerator;

class PhpCompiler extends Compiler
{
    /** @var string */
    protected $language = 'php';

    /**
     * {@inheritdoc}
     */
    protected function processXmlFields(Schema $schema, $data)
    {
        parent::processXmlFields($schema, $data);

        foreach ($schema->getFields() as &$field) {
            if ($enum = $field->getOption('enum')) {
                // search for key by value
                $enumKey = null;
                foreach ($enum->getValues() as $key => $value) {
                    if (strtolower($value) == strtolower($field->getDefault())) {
                        $enumKey = $key;
                        break;
                    }
                }

                if ($enumKey) {
                    if (!$phpOptions = $schema->getOptionSubOption('php', 'enums')) {
                        $phpOptions = $schema->getOption('php');
                    }

                    $namespace = $phpOptions['namespace'];
                    if (substr($namespace, 0, 1) == '\\') {
                        $namespace = substr($namespace, 1);
                    }

                    $className =
                        sprintf('%s\\%s%sV%d',
                            $namespace,
                            StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                            StringUtils::toCamelFromSlug($enum->getName()),
                            $schema->getId()->getVersion()->getMajor()
                        )
                    ;

                    $field->setOptionSubOption('php', 'class_name', $className);
                    $field->setOptionSubOption('php', 'default', sprintf('%s::%s()', substr($className, strrpos($className, '\\')+1), strtoupper($enumKey)));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createGenerator(Schema $schema)
    {
        return new PhpGenerator($schema);
    }
}
