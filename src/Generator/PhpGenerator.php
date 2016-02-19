<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class PhpGenerator extends Generator
{
    /** @var string */
    protected $language = 'php';

    /** @var string */
    protected $extension = '.php';

    /**
     * {@inheritdoc}
     */
    public function generate(SchemaDescriptor $schema)
    {
        foreach ($schema->getFields() as $field) {
            $this->updateFieldOptions($schema, $field);
        }

        return parent::generate($schema);
    }

    /**
     * Adds and updates field php options.
     *
     * @param SchemaDescriptor $schema
     * @param FieldDescriptor  $field
     *
     * @return FieldDescriptor
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
        if ($enum = $field->getEnum()) {
            // search for key by value
            $enumKey = null;
            foreach ($enum->getValues() as $key => $value) {
                if (strtolower($value) == strtolower($field->getDefault())) {
                    $enumKey = $key;
                    break;
                }
            }

            if ($enumKey) {
                if (!$phpOptions = $schema->getLanguageKey('php', 'enums')) {
                    $phpOptions = $schema->getLanguage('php');
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

                $field->setLanguageKey('php', 'class_name', $className);
                $field->setLanguageKey('php', 'default', sprintf('%s::%s()', substr($className, strrpos($className, '\\') + 1), strtoupper($enumKey)));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplates()
    {
        return $this->schema->isMixinSchema()
            ? [
                'MessageInterface.php.twig' => '{className}',
                'Interface.php.twig' => '{className}V{major}',
                'Mixin.php.twig' => '{className}V{major}Mixin',
                'Trait.php.twig' => '{className}V{major}Trait',
            ]
            : [
                'MessageInterface.php.twig' => '{className}',
                'AbstractMessage.php.twig' => '{className}V{major}',
            ]
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumTemplate()
    {
        return 'Enum.php.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTarget($filename, $directory = null, $isLatest = false)
    {
        $filename = str_replace([
            '{className}',
        ], [
            StringUtils::toCamelFromSlug($this->schema->getId()->getMessage()),
        ], $filename);

        $directory = str_replace('\\', '/', $this->schema->getLanguageKey('php', 'namespace'));

        return parent::getTarget($filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getParameters()
    {
        return array_merge(
            parent::getParameters(),
            [
                'className' => StringUtils::toCamelFromSlug($this->schema->getId()->getMessage()),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function generateEnums()
    {
        $enums = $this->schema->getEnums();

        foreach ($enums as $enum) {
            if (!$phpOptions = $this->schema->getLanguageKey('php', 'enums')) {
                $phpOptions = $this->schema->getLanguage('php');
            }

            $namespace = $phpOptions['namespace'];
            if (substr($namespace, 0, 1) == '\\') {
                $namespace = substr($namespace, 1);
            }

            $className =
                sprintf('%s%sV%d',
                    StringUtils::toCamelFromSlug($this->schema->getId()->getMessage()),
                    StringUtils::toCamelFromSlug($enum->getName()),
                    $this->schema->getId()->getVersion()->getMajor()
                )
            ;

            $filename =
                sprintf('%s/%s/%s%s',
                    $this->output,
                    str_replace('\\', '/', $namespace),
                    str_replace('\\', '/', $className),
                    $this->extension
                )
            ;

            $this->renderFile(
                $this->getEnumTemplate(),
                $filename,
                array_merge($this->getParameters(), [
                    'namespace' => $namespace,
                    'className' => $className,
                    'options' => $enum->getValues(),
                    'is_int' => is_int(current($enum->getValues())),
                ])
            );
        }
    }
}
