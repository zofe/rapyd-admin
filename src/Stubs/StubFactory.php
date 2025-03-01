<?php

namespace Zofe\Rapyd\Stubs;

use Zofe\Rapyd\Stubs\Concerns\FileHelpers as StubFileHelpers;

class StubFactory
{

    use StubFileHelpers;

    /**
     * The stub file content
     *
     * @var string
     */
    protected $stubContent;


    /**
     * The generated content from stub file content
     *
     * @var string
     */
    protected $generatedContent;


    /**
     * Generate content from stub
     *
     * @param  string $stubFilePath
     * @param  array  $replacers
     *
     * @return string
     */
    public function make(string $stubFilePath, array $replacers = [])
    {

        $this->stubContent = $this->getFileContent($stubFilePath);

        return $this->buildUseableContentFromStubContent($replacers);
    }


    /**
     * Get the stub file content
     *
     * @return string
     */
    public function getStubContent()
    {

        return $this->stubContent;
    }


    /**
     * Get the generated content
     *
     * @return string
     */
    public function getGeneratedContent()
    {

        return $this->generatedContent;
    }


    /**
     * Build up the useable content from stub content on given replaceable list
     *
     * @param  array  $replacers
     * @return string
     */
    protected function buildUseableContentFromStubContent(array $replacers)
    {

        $this->generatedContent = $this->stubContent;

        foreach ($replacers as $key => $value) {

            if (is_array($value)) {

                if (empty($value)) {
                    $value = '[]';
                } elseif (count($value) == 1 && $value[0][0] == '[' && $value[0][strlen($value[0]) - 1] == ']') {
                    $value = '["'
                        . implode(
                            '", "',
                            array_map(
                                'trim',
                                explode(
                                    ',',
                                    str_replace('[', '', str_replace(']', '', $value[0]))
                                )
                            )
                        )
                        . '"]';
                } else {
                    $value = '["'.implode('", "', $value).'"]';
                }

                $this->replaceInStub($key, $value);

                continue;
            }

            if (is_bool($value)) {

                $value = $value ? 'true' : 'false';
            }

            $this->replaceInStub($key, $value);
        }

        return $this->getGeneratedContent();
    }


    /**
     * Replace the occurrence of target string using the provided value
     *
     * @param  string  $key
     * @param  string  $content
     *
     * @return self
     */
    protected function replaceInStub(string $key, string $content)
    {

        $pattern = "/\{\{\s*$key\s*\}\}/";

        $this->generatedContent = preg_replace($pattern, $content, $this->generatedContent);

        return $this;
    }

}
