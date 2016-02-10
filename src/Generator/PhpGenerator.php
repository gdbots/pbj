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
    public function setSchema(SchemaDescriptor $schema)
    {
        parent::setSchema($schema);

        foreach ($this->schema->getFields() as $field) {
            $field = $this->updateFieldOptions($field);
        }
    }

    /**
     * Adds and updates field php options.
     *
     * @param FieldDescriptor $field
     *
     * @return FieldDescriptor
     */
    protected function updateFieldOptions(FieldDescriptor $field)
    {
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
                if (!$phpOptions = $this->schema->getOptionSubOption('php', 'enums')) {
                    $phpOptions = $this->schema->getOption('php');
                }

                $namespace = $phpOptions['namespace'];
                if (substr($namespace, 0, 1) == '\\') {
                    $namespace = substr($namespace, 1);
                }

                $className =
                    sprintf('%s\\%s%sV%d',
                        $namespace,
                        StringUtils::toCamelFromSlug($this->schema->getId()->getMessage()),
                        StringUtils::toCamelFromSlug($enum->getName()),
                        $this->schema->getId()->getVersion()->getMajor()
                    )
                ;

                $field->setOptionSubOption('php', 'class_name', $className);
                $field->setOptionSubOption('php', 'default', sprintf('%s::%s()', substr($className, strrpos($className, '\\') + 1), strtoupper($enumKey)));
            }
        }

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplates()
    {
        return $this->schema->isMixin()
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

        $directory = str_replace('\\', '/', $this->schema->getOptionSubOption('php', 'namespace'));

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
    public function generateEnums()
    {
        $enums = $this->schema->getOption('enums', []);

        foreach ($enums as $enum) {
            if (!$phpOptions = $this->schema->getOptionSubOption('php', 'enums')) {
                $phpOptions = $this->schema->getOption('php');
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
