<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;

class JsGenerator extends Generator
{
    const LANGUAGE = 'js';
    const EXTENSION = '.js';

    /**
     * {@inheritdoc}
     */
    protected function updateFieldOptions(SchemaDescriptor $schema, FieldDescriptor $field)
    {
        if ($enum = $field->getEnum()) {
            if (!$instance = $field->getLanguage(static::LANGUAGE)->get('instance')) {
                $instance = [
                    'package'   => $enum->getLanguage(static::LANGUAGE)->get('package'),
                    'classname' => StringUtils::toCamelFromSlug($enum->getId()->getName()),
                ];

                $field->getLanguage(static::LANGUAGE)->set('instance', $instance);
            }

            if (null === $field->getLanguage(static::LANGUAGE)->get('default', null)) {
                $default = $field->getDefault();
                if (is_array($default)) {
                    $default = count($default) ? current($default) : null;
                }

                $enumKey = 'unknown';
                if ($enum->hasValue($default)) {
                    $enumKey = $enum->getKeyByValue($default);
                }

                $field->getLanguage(static::LANGUAGE)->set('default', sprintf('%s.%s', $instance['classname'], strtoupper($enumKey)));

                if (strlen($default) === 0) {
                    $field->getLanguage(static::LANGUAGE)->set('hide_default', true);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTarget(SchemaDescriptor $schema, $filename, $directory = null, $isLatest = false)
    {
        $id = $schema->getId();
        $directory = $schema->getLanguage(static::LANGUAGE)->get('directory');

        if (null === $directory) {
            $directory = sprintf(
                '%s/%s%s%s',
                $id->getVendor(),
                $id->getPackage(),
                $id->getCategory() ? "/{$id->getCategory()}" : '',
                $schema->isMixinSchema() ? "/{$id->getMessage()}" : ''
            );
        }

        $filename = str_replace(
            ['{className}'],
            [StringUtils::toCamelFromSlug($id->getMessage())],
            $filename
        );

        return parent::getSchemaTarget($schema, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        if (!$schema->isMixinSchema()) {
            return ['message.twig' => '{className}V{major}'];
        }

        $templates = ['mixin.twig' => '{className}V{major}Mixin'];
        if (count($schema->getMixins()) || $schema->getLanguage(static::LANGUAGE)->get('insertion-points')) {
            $templates['trait.twig'] = '{className}V{major}Trait';
        }

        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function generateEnum(EnumDescriptor $enum)
    {
        $directory = $enum->getLanguage(static::LANGUAGE)->get('directory');
        if (null === $directory) {
            $directory = sprintf(
                '%s/%s/enum',
                $enum->getId()->getVendor(),
                $enum->getId()->getPackage()
            );
        }

        $directory = trim($directory, '/');
        $filename = sprintf(
            '%s/%s/%s%s',
            $this->compileOptions->getOutput(),
            $directory,
            StringUtils::toCamelFromSlug($enum->getId()->getName()),
            static::EXTENSION
        );

        $response = new GeneratorResponse();
        $response->addFile($this->renderFile(
            'enum.twig',
            $filename,
            [
                'enum'      => $enum,
                'className' => StringUtils::toCamelFromSlug($enum->getId()->getName()),
                'isInt'     => is_int(current($enum->getValues())),
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

        // import statements: removed duplicate
        if (preg_match_all('/\nimport\s(.*)\sfrom\s\'(.*)\';/', $code, $matches) !== false) {
            $unique = array_unique($matches[1]);

            foreach ($matches[1] as $key => $match) {
                if (in_array($match, $unique)) {
                    unset($unique[array_search($match, $unique)]);
                } else {
                    $code = preg_replace(sprintf("/%s/", str_replace('/', '\/', $matches[0][$key])), '', $code, 1);
                }
            }
        }

        // import statements: sorting
        if (preg_match_all('/\nimport\s(.*);/', $code, $matches) !== false) {
            $unique = array_unique($matches[1]);

            asort($unique);
            $unique = array_values($unique);

            foreach ($matches[1] as $key => $match) {
                $from = sprintf("\nimport %s;", $match);
                $to = sprintf("\nimport %s[import_tmp];", $unique[$key]);

                $code = str_replace($from, $to, $code);
            }

            $code = preg_replace("/\[import_tmp\];/", ';', $code);
        }

        // generate replacements
        $code = str_replace(
            [
                ';;',
                "\n\n\n",
                "{\n\n",
                "{\n  \n}",
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
