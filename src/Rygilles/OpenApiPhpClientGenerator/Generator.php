<?php

namespace Rygilles\OpenApiPhpClientGenerator;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_TemplateWrapper;


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
	 * Output interface if running binary
	 *
	 * @var OutputInterface
	 */
	protected $outputInterface;

	/**
	 * OpenAPI File content decoded
	 *
	 * @var mixed
	 */
	protected $openApiFileContent;

	/**
	 * Generated managers data
	 *
	 * @var mixed[]
	 */
	protected $outputManagersData = [];

	/**
	 * Generated resources data
	 *
	 * @var mixed[]
	 */
	protected $outputResourcesData = [];

	/**
	 * Twig templates environment
	 *
	 * @var Twig_Environment
	 */
	protected $twigEnv;

	/**
	 * Twig resource template
	 *
	 * @var Twig_TemplateWrapper
	 */
	protected $resourceTemplate;

	/**
	 * Twig manager template
	 *
	 * @var Twig_TemplateWrapper
	 */
	protected $managerTemplate;

	/**
	 * Generator constructor.
	 *
	 * @param string $openApiFilePath Path of the OpenAPI file
	 * @param string $outputPath Path where the PHP client library files will be generated
	 * @param mixed[] $options Array of options
	 * @param OutputInterface $outputInterface Output interface if running binary
	 */
	public function __construct($openApiFilePath, $outputPath, $options = [], $outputInterface = null)
	{
		$this->openApiFilePath = $openApiFilePath;
		$this->outputPath = $outputPath;
		$this->options = array_merge($this->options, $options);
		$this->outputInterface = $outputInterface;
	}

	/**
	 * Run generation of the PHP client library files
	 */
	public function generate()
	{
		// Load the OpenAPI schema file
		$this->loadOpenApiFile();

		// Make the output directory
		$this->makeOutputDirectory();

		// Gather tags
		$tags = $this->getOperationsTags();

		// Split tags by pattern ("Manager:*" and "Resource:*" tags)
		$managerTags = [];
		$resourcesTags = [];

		foreach ($tags as $tag) {
			$split = explode(':', $tag);
			if (count($split) == 2) {
				switch ($split[0]) {
					case 'Manager' :
						$managerTags[] = $split[1];
						break;
					case 'Resource' :
						$resourcesTags[] = $split[1];
						break;
				}
			}
		}

		// Load template filesystem
		$this->loadTemplates();

		$data = [
			'className' => 'MonTest',
			'namespace' => 'Test\Test'
		];
		
		die($this->resourceTemplate->render($data));

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
	 * Load Twig templates filesystem
	 */
	protected function loadTemplates()
	{
		$loader = new \Twig_Loader_Filesystem(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates');
		$this->twigEnv = new Twig_Environment($loader, ['cache' => false]);

		$this->managerTemplate = $this->twigEnv->load('manager.php.twig');
		$this->resourceTemplate = $this->twigEnv->load('resource.php.twig');
	}

	/**
	 * Grab operations tags from OpenAPI schema
	 *
	 * @return string[]
	 */
	protected function getOperationsTags()
	{
		$tags = [];

		foreach ($this->openApiFileContent['paths'] as $path) {
			foreach ($path as $httpMethod => $operation) {
				if (isset($operation['tags'])) {
					foreach ($operation['tags'] as $tag) {
						if (!in_array($tag, $tags)) {
							$tags[] = $tag;
						}
					}
				}
			}
		}

		return $tags;
	}

	/**
	 * Load the OpenAPI file content
	 */
	protected function loadOpenApiFile()
	{
		if (!is_null($this->outputInterface)) {
			$this->outputInterface->writeln('<info>Loading OpenAPI file : ' . $this->openApiFilePath . '</info>');
		}

		$fileContent = file_get_contents($this->openApiFilePath);

		// Parse the file using the right parser (json or yaml)

		$jsonException = null;

		if (!is_null($this->outputInterface)) {
			$this->outputInterface->writeln('<info>Decode JSON from OpenAPI file</info>');
		}

		try {
			$this->openApiFileContent = json_decode($fileContent, true);
		} catch (\Exception $e) {
			$jsonException = $e;
		}

		$jsonLastError = json_last_error();

		if ($jsonLastError != JSON_ERROR_NONE)
		{
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Can not decode JSON, try YAML</info>');
			}
			$this->openApiFileContent = Yaml::parse($fileContent, Yaml::PARSE_OBJECT | Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_DATETIME | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
		}

		if (!is_null($this->outputInterface)) {
			$this->outputInterface->writeln('<info>File decoded</info>');
		}
	}

	/**
	 * Make the output directory
	 */
	protected function makeOutputDirectory()
	{
		if (file_exists($this->outputPath)) {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Output directory already created (' . $this->outputPath . ')</info>');
			}
		} else {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Making output directory (' . $this->outputPath . ')</info>');
			}
			mkdir($this->outputPath ,0755, true);
		}
	}
}