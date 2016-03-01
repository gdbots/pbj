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

            $enumKey = $enum->hasValue(strtoupper($field->getDefault()))
                ? $field->getDefault()
                : 'unknown'
            ;

            $field->setLanguageKey('php', 'class_name', $className);
            $field->setLanguageKey('php', 'default', sprintf('%s::%s()', substr($className, strrpos($className, '\\') + 1), strtoupper($enumKey)));
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

        $directory = str_replace('\\', '/', $schema->getLanguageKey('php', 'namespace'));

        return parent::getSchemaTarget($schema, $filename, $directory, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaTemplates(SchemaDescriptor $schema)
    {
        return $schema->isMixinSchema()
            ? [
                'MessageInterface.php.twig' => '{className}',
                'Interface.php.twig' => '{className}V{major}',
                'Mixin.php.twig' => '{className}V{major}Mixin',
                'Trait.php.twig' => '{className}V{major}Trait',
            ]
            : [
                'MessageInterface.php.twig' => '{className}',
                'Message.php.twig' => '{className}V{major}',
            ]
        ;
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
            'Enum.php.twig',
            $filename,
            [
                'enum' => $enum,
                'className' => StringUtils::toCamelFromSlug($enum->getId()->getName()),
                'isInt' => is_int(current($enum->getValues())),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function generateManifest(array $schemas)
    {
        // store in root - current working directory
        $filename = sprintf('%s/pbj-schemas.php', getcwd());

        $messages = [];

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
        foreach ($schemas as $schema) {
            if ($schema->isMixinSchema()) {
                continue;
            }

            if (!array_key_exists($schema->getId()->getCurie(), $messages)) {
                $messages[$schema->getId()->getCurie()] = sprintf(
                    '%s\%sV%d',
                    $schema->getLanguageKey('php', 'namespace'),
                    StringUtils::toCamelFromSlug($schema->getId()->getMessage()),
                    $schema->getId()->getVersion()->getMajor()
                );
            }

            if (SchemaStore::hasOtherSchemaMajorRev($schema->getId())) {
                foreach (SchemaStore::getOtherSchemaMajorRev($schema->getId()) as $s) {
                    if (!array_key_exists($s->getId()->getCurieWithMajorRev(), $messages)) {
                        $messages[$s->getId()->getCurieWithMajorRev()] = sprintf(
                            '%s\%sV%d',
                            $s->getLanguageKey('php', 'namespace'),
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

        $this->renderFile(
            'pbj-schemas.php.twig',
            $filename,
            [
                'messages' => $messages,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, $parameters)
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
                "{\n    \n}",
            ], [
                ';',
                "\n\n",
                "{\n}",
            ],
            $code
        );

        return $code;
    }
}
