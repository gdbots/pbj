<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\EnumDescriptor;
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
                $namespace = $enum->getLanguageKey('php', 'namespace');
                if (substr($namespace, 0, 1) == '\\') {
                    $namespace = substr($namespace, 1);
                }

                $className =
                    sprintf('%s\\%s',
                        $namespace,
                        StringUtils::toCamelFromSlug($enum->getId()->getName())
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
    protected function getSchemaTemplates()
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
    public function generateEnum(EnumDescriptor $enum)
    {
        $namespace = $enum->getLanguageKey('php', 'namespace');
        if (substr($namespace, 0, 1) == '\\') {
            $namespace = substr($namespace, 1);
        }

        $className = StringUtils::toCamelFromSlug($enum->getId()->getName());

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
                'enumClassName' => $className,
                'options' => $enum->getValues(),
                'isInt' => is_int(current($enum->getValues())),
            ])
        );
    }
}
