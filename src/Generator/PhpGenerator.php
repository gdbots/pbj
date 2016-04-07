<?php

namespace Gdbots\Pbjc\Generator;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\EnumDescriptor;
use Gdbots\Pbjc\FieldDescriptor;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\SchemaStore;

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
            if (!$className = $field->getLanguage('php')->get('classname')) {
                $namespace = $enum->getLanguage('php')->get('namespace');
                if (substr($namespace, 0, 1) == '\\') {
                    $namespace = substr($namespace, 1);
                }

                $className =
                    sprintf('%s\\%s',
                        $namespace,
                        StringUtils::toCamelFromSlug($enum->getId()->getName())
                    )
                ;

                $field->getLanguage('php')->set('classname', $className);
            }

            if (null === $field->getLanguage('php')->get('default', null)) {
                $default = $field->getDefault();
                if (is_array($default)) {
                    $default = count($default) ? current($default) : null;
                }

                $enumKey = 'unknown';
                if ($default && $enum->hasValue($default)) {
                    $enumKey = $enum->getKeyByValue($default);
                }

                $field->getLanguage('php')->set('default', sprintf('%s::%s()', substr($className, strrpos($className, '\\') + 1), strtoupper($enumKey)));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTarget(SchemaDescriptor $schema, $filename, $directory = null, $isLatest = false)
    {
        $filename = str_replace([
            '{className}',
        ], [
            StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
        ], $filename);

        $directory = str_replace('\\', '/', $schema->getLanguage('php')->get('namespace'));

        return parent::getSchemaTarget($schema, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        return $schema->isMixinSchema()
            ? [
                'curie-interface.twig' => '{className}',
                'curie-major-interface.twig' => '{className}V{major}',
                'mixin.twig' => '{className}V{major}Mixin',
                'trait.twig' => '{className}V{major}Trait',
            ]
            : [
                'curie-interface.twig' => '{className}',
                'message.twig' => '{className}V{major}',
            ]
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function generateEnum(EnumDescriptor $enum)
    {
        $namespace = $enum->getLanguage('php')->get('namespace');
        if (substr($namespace, 0, 1) == '\\') {
            $namespace = substr($namespace, 1);
        }

        $className = StringUtils::toCamelFromSlug($enum->getId()->getName());

        $filename =
            sprintf('%s/%s/%s%s',
                $this->compileOptions->getOutput(),
                str_replace('\\', '/', $namespace),
                str_replace('\\', '/', $className),
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
    public function generateManifest(array $schemas)
    {
        $messages = [];

        if (!$filename = $this->compileOptions->getManifest()) {
            return;
        }

        // extract previous schemas
        if (file_exists($filename)) {
            $content = file_get_contents($filename);

            if (preg_match_all('/\'([a-z0-9-]+:[a-z0-9\.-]+:[a-z0-9-]+?:[a-z0-9-]+(:v[0-9]+)?)\' => \'(.*)\'/', $content, $matches) !== false) {
                foreach ($matches[1] as $key => $value) {
                    $messages[$value] = $matches[3][$key];
                }
            }
        }

        // merge with selected schemas (only non-mixin schema's)

        /** @var SchemaDescriptor $schema */
        foreach ($schemas as $schema) {
            if ($schema->isMixinSchema()) {
                continue;
            }

            if (!array_key_exists($schema->getId()->getCurie(), $messages)) {
                $messages[$schema->getId()->getCurie()] = sprintf(
                    '%s\%sV%d',
                    $schema->getLanguage('php')->get('namespace'),
                    StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                    $schema->getId()->getVersion()->getMajor()
                );
            }

            if (SchemaStore::hasOtherSchemaMajorRev($schema->getId())) {
                /** @var SchemaDescriptor $s */
                foreach (SchemaStore::getOtherSchemaMajorRev($schema->getId()) as $s) {
                    if (!array_key_exists($s->getId()->getCurieWithMajorRev(), $messages)) {
                        $messages[$s->getId()->getCurieWithMajorRev()] = sprintf(
                            '%s\%sV%d',
                            $s->getLanguage('php')->get('namespace'),
                            StringUtils::toCamelFromSlug($s->getId()->getMessage()),
                            $s->getId()->getVersion()->getMajor()
                        );
                    }
                }
            }
        }

        // delete invalid schemas
        foreach ($messages as $key => $value) {
            if (!SchemaStore::getSchemaById($key, true)) {
                unset($messages[$key]);
            }
        }

        $response = new GeneratorResponse();

        $response->addFile($this->renderFile(
            'manifest.twig',
            $filename,
            [
                'messages' => $messages,
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

        // use statements: removed duplicate
        if (preg_match_all('/\nuse\s(.*);/', $code, $matches) !== false) {
            $unique = array_unique($matches[1]);

            foreach ($matches[1] as $match) {
                if (in_array($match, $unique)) {
                    unset($unique[array_search($match, $unique)]);
                } else {
                    $code = preg_replace(sprintf("/\nuse\\s%s;/", str_replace('\\', '\\\\', $match)), '', $code, 1);
                }
            }
        }

        // use statements: sorting
        if (preg_match_all('/\nuse\s(.*);/', $code, $matches) !== false) {
            $unique = array_unique($matches[1]);

            asort($unique);
            $unique = array_values($unique);

            foreach ($matches[1] as $key => $match) {
                $from = sprintf("\nuse %s;", $match);
                $to = sprintf("\nuse %s[use_tmp];", $unique[$key]);

                $code = str_replace($from, $to, $code);
            }

            $code = preg_replace("/\[use_tmp\];/", ';', $code);
        }

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
