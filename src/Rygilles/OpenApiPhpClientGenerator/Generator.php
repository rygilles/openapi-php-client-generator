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
	 * Output files base namespace
	 *
	 * @var string
	 */
	protected $namespace;

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
	 * Managers operations
	 *
	 * @var mixed[]
	 */
	protected $managerOperations = [];

	/**
	 * Resources operations
	 *
	 * @var mixed[]
	 */
	protected $resourcesOperations = [];

	/**
	 * Managers data
	 *
	 * @var mixed[]
	 */
	protected $managersData = [];

	/**
	 * Resrouces data
	 *
	 * @var mixed[]
	 */
	protected $resourcesData = [];

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
	 * @param string $namespace Base namespace of generated files
	 * @param mixed[] $options Array of options
	 * @param OutputInterface $outputInterface Output interface if running binary
	 */
	public function __construct($openApiFilePath, $outputPath, $namespace, $options = [], $outputInterface = null)
	{
		$this->openApiFilePath = $openApiFilePath;
		$this->outputPath = $outputPath;
		$this->namespace = $namespace;
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

		// Parse OpenAPI file for operations

		foreach ($this->openApiFileContent['paths'] as $path => $operations) {
			foreach ($operations as $httpMethod => $operation) {
				if (isset($operation['tags'])) {
					foreach ($operation['tags'] as $tag) {
						$split = explode(':', $tag);
						if (count($split) == 2) {
							$extractedTag = $split[1];
							switch ($split[0]) {
								case 'Manager' :
									$this->prepareManager($extractedTag);
									$this->managersData[ucfirst($extractedTag)]['operations'][$operation['operationId']] = $operation;
									break;
								case 'Resource' :
									$this->prepareResource($extractedTag);
									$this->resourcesData[ucfirst($extractedTag)]['operations'][$operation['operationId']] = $operation;
									break;
							}
						}
					}
				}
			}
		}

		// Load template filesystem
		$this->loadTemplates();

		// Write files
		$this->writeTemplates();

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
	 * Write templates files
	 */
	protected function writeTemplates()
	{
		$this->writeManagersTemplates();
		$this->writeResourcesTemplates();
	}

	/**
	 * Write managers templates files
	 */
	protected function writeManagersTemplates()
	{
		foreach ($this->managersData as $managerName => $managerData) {
			$data = [
				'className' => $managerName,
				'namespace' => $this->namespace . '\Managers',
				'operations' => $managerData['operations']
			];

			$filePath = $this->outputPath . PATH_SEPARATOR . 'Managers' . PATH_SEPARATOR . $managerName . '.php';
			die($filePath);
			//file_put_contents($filePath, $this->resourceTemplate->render($data));
		}
	}

	/**
	 * Write resources templates files
	 */
	protected function writeResourcesTemplates()
	{

	}

	/**
	 * Prepare new manager data if not already done
	 *
	 * @param string $managerTag
	 */
	protected function prepareManager($managerTag)
	{
		// Already prepared ?
		if (isset($this->managersData[ucfirst($managerTag)])) {
			return;
		}

		$this->managersData[ucfirst($managerTag)] = [
			'className' => ucfirst($managerTag),
			'operations' => []
		];
	}

	/**
	 * Prepare new resource data if not already done
	 *
	 * @param string $resourceTag
	 */
	protected function prepareResource($resourceTag)
	{
		// Already prepared ?
		if (isset($this->resourcesData[ucfirst($resourceTag)])) {
			return;
		}

		$this->resourcesData[ucfirst($resourceTag)] = [
			'className' => ucfirst($resourceTag),
			'operations' => []
		];
	}

	/**
	 * Parse Resource path operation
	 *
	 * @param string $resourceTag Resource tag extracted
	 * @param string $path Api path
	 * @param mixed[] $operation OpenAPI Operation object
	 */
	protected function parseResourcePathOperation($resourceTag, $path, $operation)
	{

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