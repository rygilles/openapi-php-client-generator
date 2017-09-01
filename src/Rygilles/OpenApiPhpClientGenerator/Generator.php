<?php

namespace Rygilles\OpenApiPhpClientGenerator;

/**
 * Class Generator
 * @package Rygilles\OpenApiGenerator
 */
class Generator
{
	/**
	 * Path of the OpenAPI file
	 *
	 * @var string
	 */
	protected $openApiFilePath;

	/**
	 * Path where the PHP client library files will be generated
	 *
	 * @var string
	 */
	protected $outputPath;

	/**
	 * Array of options
	 *
	 * @var mixed[]
	 */
	protected $options = [

	];

	/**
	 * OpenAPI File content decoded
	 *
	 * @var mixed
	 */
	protected $openApiFileContent;

	/**
	 * Generator constructor.
	 *
	 * @param string $openApiFilePath Path of the OpenAPI file
	 * @param string $outputPath Path where the PHP client library files will be generated
	 * @param mixed[] $options Array of options
	 */
	public function __construct($openApiFilePath, $outputPath, $options = [])
	{
		$this->openApiFilePath = $openApiFilePath;
		$this->outputPath = $outputPath;
		$this->options = array_merge($this->options, $options);
	}

	/**
	 * Run generation of the PHP client library files
	 */
	public function generate()
	{
		// @todo Step : Load the OpenAPI schema file
		$this->loadOpenApiFile();

		// @todo Step : Parse the file using the right parser (json or yaml)
		// @todo Step : Clear the output path
		// @todo Step : Make the root directory
		// @todo Step : Root directory : Make README.md
		// @todo Step : Root directory : Make LICENSE.md
		// @todo Step : Root directory : Make .gitignore
		// @todo Step : Root directory : Make composer.json
		// @todo Step : Root directory : Make phpunit.xml
		// @todo Step : Make "src" directory
		// @todo Step : "src" directory : Make "%libNamespace%" directory/subdirectory
		// @todo Step : "%libNamespace%" subdirectory : Make "Resources" directory
		// @todo Step : "%libNamespace%" subdirectory : Make "Managers" directory
		// @todo Step : "Resources" directory : Make components/schemas as resources files
		// @todo Step : "Managers" directory : Make managers files from operations tags pattern'Manager:*'
		// @todo Step : Resources files : Make methods from operation tags pattern 'Resource:**'
		// @todo Step : "%libNamespace% subdirectory : Make "%libName%Client.php"
		// @todo Step : Make "tests" directory

	}

	/**
	 * Load the OpenAPI file content
	 */
	protected function loadOpenApiFile()
	{
		$this->openApiFileContent = json_decode($this->openApiFilePath);
	}
}