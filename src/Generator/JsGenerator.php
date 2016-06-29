<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

class JsGenerator extends Generator
{
    /** @var string */
    protected $language = 'js';

    /** @var string */
    protected $extension = '.js';

    /**
     * {@inheritdoc}
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
        if ($enum = $field->getEnum()) {
            if (!$instance = $field->getLanguage('js')->get('instance')) {
                $instance = [
                    'namespace' => $enum->getLanguage('js')->get('namespace'),
                    'classname' => StringUtils::toCamelFromSlug($enum->getId()->getName())
                ];

                $field->getLanguage('js')->set('instance', $instance);
            }

            if (null === $field->getLanguage('js')->get('default', null)) {
                $default = $field->getDefault();
                if (is_array($default)) {
                    $default = count($default) ? current($default) : null;
                }

                $enumKey = 'unknown';
                if ($enum->hasValue($default)) {
                    $enumKey = $enum->getKeyByValue($default);
                }

                $field->getLanguage('js')->set('default', sprintf('%s.%s', $instance['classname'], strtoupper($enumKey)));

                if (strlen($default) === 0) {
                    $field->getLanguage('js')->set('hide_default', true);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTarget(SchemaDescriptor $schema, $filename, $directory = null, $isLatest = false)
    {
        $directory = $schema->getLanguage('js')->get('namespace');

        return parent::getSchemaTarget($schema, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        return $schema->isMixinSchema()
            ? [
                'mixin.twig' => '{message}-v{major}-mixin',
            ]
            : [
                'curie-interface.twig' => '{message}',
                'message.twig' => '{message}-v{major}',
            ]
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function generateEnum(EnumDescriptor $enum)
    {
        $filename =
            sprintf('%s/%s/%s%s',
                $this->compileOptions->getOutput(),
                $enum->getLanguage('js')->get('namespace'),
                $enum->getId()->getName(),
                $this->extension
            )
        ;

        $response = new GeneratorResponse();

        $response->addFile($this->renderFile(
            'enum.twig',
            $filename,
            [
                'enum' => $enum,
                'className' => StringUtils::toCamelFromSlug($enum->getId()->getName()),
                'isInt' => is_int(current($enum->getValues())),
            ]
        ));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, array $parameters)
    {
        $code = parent::render($template, $parameters);

        // @todo: use statements: removed duplicate
        // @todo: use statements: sorting

        // generate replacements
        $code = str_replace(
            [
                ';;',
                "\n\n\n",
                "{\n\n",
                "{\n    \n}",
                "}\n\n}",
            ], [
                ';',
                "\n\n",
                "{\n",
                "{\n}",
                "}\n}",
            ],
            $code
        );

        return $code;
    }
}
