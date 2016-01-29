<?php

namespace Gdbots\Pbjc\Generator;

class JsonGenerator extends Generator
{
    /** @var string */
    protected $language = 'json';

    /** @var string */
    protected $extension = '.json';

    /**
     * {@inheritdoc}
     */
    protected function getTemplates()
    {
        return [
                'Message.json.twig'  => '{vendor}.{package}.{category}.{className}.{version}'
            ]
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumTemplate()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTarget($output, $filename, $directory = null, $isLatest = false)
    {
        if ($isLatest) {
            $filename = str_replace('{version}', 'latest', $filename);
        }

        return parent::getTarget($output, $filename, false, $isLatest);
    }

    /**
     * {@inheritdoc}
     */
    protected function printFile($template, $target, $parameters)
    {
        echo sprintf("<pre>Filename = %s\n\n%s</pre><hr />", $target, $this->render($template, $parameters));
    }

    /**
     * {@inheritdoc}
     */
    protected function render($template, $parameters)
    {
        return $this->prettyPrint(parent::render($template, $parameters));
    }

    /**
     * Make a JSON string look good :-).
     *
     * @param string $json
     *
     * @return string
     */
    public function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $prevChar = '';
        $inQuotes = false;
        $endsLineLevel = null;

        for ($i = 0; $i < strlen($json); $i++) {
            $char = $json[$i];
            $newLineLevel = null;
            $post = '';

            if ($endsLineLevel !== null) {
                $newLineLevel = $endsLineLevel;
                $endsLineLevel = null;
            }

            if ($char === '"' && $prevChar != '\\') {
                $inQuotes = !$inQuotes;
            } elseif (!$inQuotes) {
                switch ($char) {
                    case '}':
                    case ']':
                        $level--;
                        $endsLineLevel = null;
                        $newLineLevel = $level;
                        break;

                    case '{':
                    case '[':
                        $level++;
                        $endsLineLevel = $level;
                        break;

                    case ',':
                        $endsLineLevel = $level;
                        break;

                    case ':':
                        $post = ' ';
                        break;

                    case " ":
                    case "\t":
                    case "\n":
                    case "\r":
                        $char = "";
                        $endsLineLevel = $newLineLevel;
                        $newLineLevel = null;
                        break;
                }
            }

            if ($newLineLevel !== null) {
                $result .= "\n".str_repeat("  ", $newLineLevel);
            }

            $result .= $char.$post;
            $prevChar = $char;
        }

        return $result;
    }
}
