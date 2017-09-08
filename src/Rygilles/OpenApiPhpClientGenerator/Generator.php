<?php

namespace Rygilles\OpenApiPhpClientGenerator;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_Extension_Debug;
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
	 * Resources data
	 *
	 * @var mixed[]
	 */
	protected $resourcesData = [];

	/**
	 * Main client data
	 *
	 * @var mixed[]
	 */
	protected $mainClientData = [];

	/**
	 * Main exception data
	 *
	 * @var mixed[]
	 */
	protected $mainExceptionData = [];

	/**
	 * Twig templates environment
	 *
	 * @var Twig_Environment
	 */
	protected $twigEnv;

	/**
	 * Twig manager template
	 *
	 * @var Twig_TemplateWrapper
	 */
	protected $managerTemplate;

	/**
	 * Twig resource template
	 *
	 * @var Twig_TemplateWrapper
	 */
	protected $resourceTemplate;

	/**
	 * Twig main client template
	 *
	 * @var Twig_TemplateWrapper
	 */
	protected $mainClientTemplate;

	/**
	 * Twig main exception template
	 *
	 * @var Twig_TemplateWrapper
	 */
	protected $mainExceptionTemplate;

	/**
	 * Generator constructor.
	 *
	 * @param string $openApiFilePath Path of the OpenAPI file
	 * @param string $outputPath Path where the PHP client library files will be generated
	 * @param string $namespace Base namespace of generated files
	 * @param OutputInterface $outputInterface Output interface if running binary
	 */
	public function __construct($openApiFilePath, $outputPath, $namespace, $outputInterface = null)
	{
		$this->openApiFilePath = $openApiFilePath;
		$this->outputPath = $outputPath;
		$this->namespace = $namespace;
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
		$this->parseOperations();

		// Add in path parameters map based on resource properties
		$this->computeInPathParameters();

		// Add operation responses makers
		$this->computeOperationsResponsesMakers();

		// Make the main client data
		$this->makeMainClient();

		// Make the main exception data
		$this->makeMainException();

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
	 * Make the main exception data
	 */
	protected function makeMainException()
	{
		$this->mainExceptionData['uses'] = [
			'RuntimeException'
		];
		$this->mainExceptionData['className'] = 'ApiException';
		$this->mainExceptionData['classPhpDocTitle'] = 'Api Exception class';
		$this->mainExceptionData['namespace'] = $this->namespace . '\\Exceptions';
		$this->mainExceptionData['extends'] = 'RuntimeException';
	}

	/**
	 * Make the main client data
	 */
	protected function makeMainClient()
	{
		$this->mainClientData['uses'] = [
			'GuzzleHttp\Client as GuzzleClient'
		];
		$this->mainClientData['managers'] = [];
		$this->mainClientData['className'] = 'ApiClient';
		$this->mainClientData['classPhpDocTitle'] = $this->openApiFileContent['info']['title'] . ' client class';
		$this->mainClientData['classPhpDocTitle'] .= $this->openApiFileContent['info']['version'] ? (' (version ' . $this->openApiFileContent['info']['version'] . ')') : '';
		$this->mainClientData['namespace'] = $this->namespace;

		$this->mainClientData['info'] = $this->openApiFileContent['info'];

		$firstKey = array_keys($this->openApiFileContent['servers'])[0];
		$this->mainClientData['apiBaseUrl'] = $this->openApiFileContent['servers'][$firstKey]['url'];

		// Security
		// @todo Not only OAuth support
		// @todo Scopes support ?
		$this->mainClientData['useBearerToken'] = false;
		if (isset($this->openApiFileContent['security'])) {
			foreach ($this->openApiFileContent['security'] as $s) {
				foreach ($s as $securityRequirement => $scope) {
					// Ignoring scopes...
					if (!isset($this->mainClientData['security'])) {
						$this->mainClientData['security'] = [];
					}
					$this->mainClientData['security'][$securityRequirement] = $this->openApiFileContent['components']['securitySchemes'][$securityRequirement];

					if ($this->openApiFileContent['components']['securitySchemes'][$securityRequirement]['type'] == 'http') {
						if ($this->openApiFileContent['components']['securitySchemes'][$securityRequirement]['scheme'] == 'bearer') {
							$this->mainClientData['useBearerToken'] = true;
						}
					}

					if ($this->openApiFileContent['components']['securitySchemes'][$securityRequirement]['type'] == 'oauth2') {
						$this->mainClientData['useBearerToken'] = true;
					}
				}
			}
		}

		// Add extra Guzzle classes in uses
		if ($this->mainClientData['useBearerToken']) {
			$this->mainClientData['uses'][] = 'Psr\Http\Message\RequestInterface';
			$this->mainClientData['uses'][] = 'GuzzleHttp\HandlerStack';
			$this->mainClientData['uses'][] = 'GuzzleHttp\Handler\CurlHandler';
			$this->mainClientData['uses'][] = 'GuzzleHttp\Middleware';
		}

		foreach ($this->managersData as $managerName => $managerData) {
			$this->mainClientData['uses'][] = $this->namespace . '\\Managers\\' . $managerName . 'Manager';
			$this->mainClientData['managers'][$managerName] = [
				'name' => $managerName,
				'className' => $managerName . 'Manager',
				'lowerCamelCaseClassName' => lcfirst($managerName . 'Manager')
			];
		}
	}
	
	/**
	 * Parse OpenAPI file for operations
	 * 
	 * @throws Exception
	 */
	protected function parseOperations()
	{
		foreach ($this->openApiFileContent['paths'] as $path => $operations) {
			foreach ($operations as $httpMethod => $operation) {
				if (isset($operation['tags'])) {
					$extractedTags = [];
					foreach ($operation['tags'] as $tag) {
						$split = explode(':', $tag);
						if (count($split) == 2) {
							switch ($split[0]) {
								case 'Manager' :
									if (!isset($extractedTags['Managers'])) {
										$extractedTags['Managers'] = [];
									}
									$extractedTags['Managers'][] = $split[1];
									break;
								case 'Resource' :
									if (!isset($extractedTags['Resources'])) {
										$extractedTags['Resources'] = [];
									}
									$extractedTags['Resources'][] = $split[1];
									break;
							}
						}
					}
					
					$resolvedResponseReference = $this->analyzeRouteOperationResponses($path, $httpMethod, $operation);
					
					foreach ($extractedTags as $tagType => $typeTags) {
						foreach ($typeTags as $typeTag) {
							switch ($tagType) {
								case 'Managers' :
									
									//$relatedResource = null;
									
									/*
									if (isset($extractedTags['Resources'])) {
										$firstKey = array_keys($extractedTags['Resources'])[0];
										$relatedResource = $extractedTags['Resources'][$firstKey];

										// Add class file "use"
										if (!isset($this->managersData[ucfirst($typeTag)]['uses'])) {
											$this->managersData[ucfirst($typeTag)]['uses'] = [];
										}
										if (!in_array($this->namespace . '\\Resources\\' . $relatedResource, $this->managersData[ucfirst($typeTag)]['uses'])) {
											$this->managersData[ucfirst($typeTag)]['uses'][] = $this->namespace . '\\Resources\\' . $relatedResource;
										}
									}
									*/
									
									$this->prepareManager($typeTag);
									
									// Add the response resolved reference resource use if exists
									if (!is_null($resolvedResponseReference)) {
										if (!isset($this->managersData[ucfirst($typeTag)]['uses'])) {
											$this->managersData[ucfirst($typeTag)]['uses'] = [];
										}
										if (!in_array($this->namespace . '\\Resources\\' . $resolvedResponseReference['name'], $this->managersData[ucfirst($typeTag)]['uses'])) {
											$this->managersData[ucfirst($typeTag)]['uses'][] = $this->namespace . '\\Resources\\' . $resolvedResponseReference['name'];
										}
									}
									
									$this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']] = [
										'path' => $path,
										'httpMethod' => $httpMethod,
										'operation' => $operation,
										'definitionParameters' => $this->getRouteOperationDefinitionParameters(true, $path, $httpMethod, $operation),
										'summary' => $this->getRouteOperationSummary($path, $httpMethod, $operation),
										'description' => $this->getRouteOperationDescription($path, $httpMethod, $operation),
										'exceptedResponseCode' => $this->getRouteOperationExceptedResponseCode($operation)
									];
									
									// Add response resource return
									if (!is_null($resolvedResponseReference)) {
										$this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']]['return'] = $resolvedResponseReference['name'];
									}
									
									/*
									if (!is_null($relatedResource)) {
										$this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']]['relatedResource'] = $relatedResource;
									}
									*/
									break;
								
								case 'Resources' :
									$this->prepareResource($typeTag);
									
									// Add the response resolved reference resource use if exists
									if (!is_null($resolvedResponseReference)) {
										if (!isset($this->resourcesData[ucfirst($typeTag)]['uses'])) {
											$this->resourcesData[ucfirst($typeTag)]['uses'] = [];
										}
										if (!in_array($this->namespace . '\\Resources\\' . $resolvedResponseReference['name'], $this->resourcesData[ucfirst($typeTag)]['uses'])) {
											$this->resourcesData[ucfirst($typeTag)]['uses'][] = $this->namespace . '\\Resources\\' . $resolvedResponseReference['name'];
										}
									}
									
									$this->resourcesData[ucfirst($typeTag)]['routes'][$operation['operationId']] = [
										'path' => $path,
										'httpMethod' => $httpMethod,
										'operation' => $operation,
										'definitionParameters' => $this->getRouteOperationDefinitionParameters(false, $path, $httpMethod, $operation),
										'summary' => $this->getRouteOperationSummary($path, $httpMethod, $operation),
										'description' => $this->getRouteOperationDescription($path, $httpMethod, $operation),
										'exceptedResponseCode' => $this->getRouteOperationExceptedResponseCode($operation)
									];
									
									// Add response resource return
									if (!is_null($resolvedResponseReference)) {
										$this->resourcesData[ucfirst($typeTag)]['routes'][$operation['operationId']]['return'] = $resolvedResponseReference['name'];
									}
									
									break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Add in path parameters map based on resoruce properties
	 */
	protected function computeInPathParameters()
	{
		foreach ($this->openApiFileContent['paths'] as $path => $operations) {
			foreach ($operations as $httpMethod => $operation) {
				if (isset($operation['tags'])) {
					$extractedTags = [];
					foreach ($operation['tags'] as $tag) {
						$split = explode(':', $tag);
						if (count($split) == 2) {
							switch ($split[0]) {
								case 'Manager' :
									if (!isset($extractedTags['Managers'])) {
										$extractedTags['Managers'] = [];
									}
									$extractedTags['Managers'][] = $split[1];
									break;
								case 'Resource' :
									if (!isset($extractedTags['Resources'])) {
										$extractedTags['Resources'] = [];
									}
									$extractedTags['Resources'][] = $split[1];
									break;
							}
						}
					}

					foreach ($extractedTags as $tagType => $typeTags) {
						foreach ($typeTags as $typeTag) {
							switch ($tagType) {
								case 'Managers' :
									$this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']]['inPathParameters'] = $this->getRouteOperationInPathParameters($operation);
									break;

								case 'Resources' :
									$this->resourcesData[ucfirst($typeTag)]['routes'][$operation['operationId']]['inPathParameters'] = $this->getRouteOperationInPathParameters($operation, ucfirst($typeTag), $this->resourcesData[ucfirst($typeTag)]['properties']);
									break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Add operation responses makers
	 */
	protected function computeOperationsResponsesMakers()
	{
		foreach ($this->openApiFileContent['paths'] as $path => $operations) {
			foreach ($operations as $httpMethod => $operation) {
				if (isset($operation['tags'])) {
					$extractedTags = [];
					foreach ($operation['tags'] as $tag) {
						$split = explode(':', $tag);
						if (count($split) == 2) {
							switch ($split[0]) {
								case 'Manager' :
									if (!isset($extractedTags['Managers'])) {
										$extractedTags['Managers'] = [];
									}
									$extractedTags['Managers'][] = $split[1];
									break;
								case 'Resource' :
									if (!isset($extractedTags['Resources'])) {
										$extractedTags['Resources'] = [];
									}
									$extractedTags['Resources'][] = $split[1];
									break;
							}
						}
					}

					foreach ($extractedTags as $tagType => $typeTags) {
						foreach ($typeTags as $typeTag) {
							switch ($tagType) {
								case 'Managers' :
									if (isset($this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']]['return'])) {
										$return = $this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']]['return'];
										if (!is_null($return)) {
											$this->managersData[ucfirst($typeTag)]['routes'][$operation['operationId']]['responseMaker'] = $this->computeOperationResponsesMaker('Managers', $typeTag, $operation, $return);
										}
									}
									break;

								case 'Resources' :
									if (isset($this->resourcesData[ucfirst($typeTag)]['routes'][$operation['operationId']]['return'])) {
										$return = $this->resourcesData[ucfirst($typeTag)]['routes'][$operation['operationId']]['return'];
										if (!is_null($return)) {
											$this->resourcesData[ucfirst($typeTag)]['routes'][$operation['operationId']]['responseMaker'] = $this->computeOperationResponsesMaker('Resources', $typeTag, $operation, $return);
										}
									}
									break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Create operation response maker
	 *
	 * @param $typeTag
	 * @param $classTypeName 'Managers' or 'Resources'
	 * @param mixed[] $operation
	 * @param string $return
	 * @param int $tabs Current indentation
	 * @param string $arrayContext
	 * @return string
	 */
	protected function computeOperationResponsesMaker($typeTag, $classTypeName, $operation, $return, $tabs = 2, $arrayContext = '')
	{
		$newTabs = $tabs + 1;
		$resourceData = $this->resourcesData[$return];

		$callBody = str_repeat("\t", $newTabs) . '$this->apiClient, ' . "\n";
		if (isset($resourceData['properties'])) {
			foreach ($resourceData['properties'] as $property) {
				if (isset($this->resourcesData[$property['type']])) {
					// Add 'use'
					switch ($classTypeName) {
						case 'Managers':
							if (!in_array($this->namespace . '\\Resources\\' . $property['type'], $this->managersData[ucfirst($typeTag)]['uses'])) {
								$this->managersData[ucfirst($typeTag)]['uses'][] = $this->namespace . '\\Resources\\' . $property['type'];
							}
							break;
						case 'Resources':
							if (!in_array($this->namespace . '\\Resources\\' . $property['type'], $this->resourcesData[ucfirst($typeTag)]['uses'])) {
								$this->resourcesData[ucfirst($typeTag)]['uses'][] = $this->namespace . '\\Resources\\' . $property['type'];
							}
							break;
					}

					$callBody .= $this->computeOperationResponsesMaker($typeTag, $classTypeName, $operation, $property['type'], $newTabs, $arrayContext . '[\'' . $property['name'] . '\']') . ', ' . "\n";
				} else {
					$callBody .= str_repeat("\t", $newTabs) . '$requestBody' . $arrayContext . '[\'' . $property['name'] . '\']' . ', ' . "\n";
				}
			}
		}
		$callBody = rtrim($callBody, (', ' . "\n"));

		$responseMaker = (($tabs > 2) ? str_repeat("\t", $tabs) : '') . 'new ' . $return . '(' . "\n" . $callBody . "\n" . str_repeat("\t", $tabs) . ')' . (($tabs == 2) ? ';' : '');

		return $responseMaker;
	}
	
	/**
	 * Analyze the route operation responses and return te first resolved response reference if exists
	 *
	 * @param string $path
	 * @param string $httpMethod
	 * @param mixed[] $operation
	 *
	 * @return mixed[]|null Return resolved reference if a response exists
	 */
	protected function analyzeRouteOperationResponses($path, $httpMethod, $operation)
	{
		if (!isset($operation['responses'])) {
			return;
		}

		foreach ($operation['responses'] as $httpCode => $response) {
			if (!isset($response['content'])) {
				continue;
			}

			$mediaType = array_keys($response['content'])[0];

			if (!isset($response['content'][$mediaType]['schema'])) {
				continue;
			}

			// Response object or reference ?
			if (isset($response['content'][$mediaType]['schema']['$ref'])) {
				$resolved = $this->resolveReference($response['content'][$mediaType]['schema']['$ref']);
				$this->makeResponseResource($resolved['name'], $resolved['target']);
				return $resolved;
			} else {
				//$schema = $response['content'][$mediaType]['schema'];
				// @todo what to do ?
			}
		}
	}

	/**
	 * Return the route operation excepted result HTTP code.
	 *
	 * @param mixed[] $operation
	 * @return int|null
	 */
	protected function getRouteOperationExceptedResponseCode($operation)
	{
		if (!isset($operation['responses'])) {
			return null;
		}

		foreach ($operation['responses'] as $httpCode => $response) {
			return $httpCode;
		}

		return null;
	}

	/**
	 * Make response resource (if not defined yet)
	 *
	 * @param string $name
	 * @param mixed[] $schema
	 */
	protected function makeResponseResource($name, $schema)
	{
		// Analyze properties for references
		if (isset($schema['properties'])) {
			foreach ($schema['properties'] as $propertyName => $property) {
				$this->prepareResource($name);

				$this->resourcesData[$name]['properties'][$propertyName]['name'] = $propertyName;

				if (isset($property['$ref'])) {
					$resolved = $this->resolveReference($property['$ref']);
					$this->resourcesData[$name]['properties'][$propertyName]['type'] = $resolved['name'];
					$this->prepareResource($resolved['name']);

					$this->makeResponseResource($resolved['name'], $resolved['target']);

					if (!isset($this->resourcesData[$resolved['name']]['properties'])) {
						$this->resourcesData[$resolved['name']]['properties'] = [];
					}

					if (!isset($this->resourcesData[$name]['uses'])) {
						$this->resourcesData[$name]['uses'] = [];
					}
					if (!in_array($this->namespace . '\\Resources\\' . $resolved['name'], $this->resourcesData[$name]['uses'])) {
						$this->resourcesData[$name]['uses'][] = $this->namespace . '\\Resources\\' . $resolved['name'];
					}

					$this->resourcesData[$name]['properties'][$propertyName]['type'] = $resolved['name'];
				} else {
					if (isset($property['type'])) {
						$this->resourcesData[$name]['properties'][$propertyName]['type'] = $property['type'];
					}
					if (isset($property['format'])) {
						$this->resourcesData[$name]['properties'][$propertyName]['format'] = $property['format'];
					}
					if (isset($property['description'])) {
						$this->resourcesData[$name]['properties'][$propertyName]['description'] = $property['description'];
					}
				}
			}
		}
	}

	/**
	 * Resolve OpenAPI reference
	 *
	 * @param string $ref
	 * @return \mixed[]
	 * @throws Exception
	 */
	protected function resolveReference($ref)
	{
		// @todo Better resolver (Only work with internal components atm)
		if (strpos($ref, '#/components/') !== 0) {
			throw new Exception('Can not resolve this $ref atm (todo) : ' . $ref);
		}

		$componentPath = str_replace('#/components/', '', $ref);
		$pathParts = explode('/', $componentPath);

		$target = $this->openApiFileContent['components'];
		$processingPathParts = ['#', 'components'];
		$targetName = '';
		foreach ($pathParts as $pathPart) {
			$processingPathParts[] = $pathPart;
			if (!isset($target[$pathPart])) {
				throw new Exception('Can not resolve $ref "' . $ref . '" : Segment not found at "' . implode('/', $processingPathParts) . '"');
			}
			$target = $target[$pathPart];
			$targetName = $pathPart;
		}

		return [
			'name' => $targetName,
			'target' => $target
		];
	}

	/**
	 * Return the phpdoc summary of a resource/manager class method
	 *
	 * @param string $path
	 * @param string $httpMethod
	 * @param mixed[] $operation
	 * @return string
	 */
	protected function getRouteOperationSummary($path, $httpMethod, $operation)
	{
		return isset($operation['summary']) ? $operation['summary'] : '';
	}

	/**
	 * Return the phpdoc description of a resource/manager class method
	 *
	 * @param string $path
	 * @param string $httpMethod
	 * @param mixed[] $operation
	 * @return string
	 */
	protected function getRouteOperationDescription($path, $httpMethod, $operation)
	{
		return isset($operation['description']) ? $operation['description'] : '';
	}

	/**
	 * Return a map of the operation path parameters
	 * With the path parameter name as the key and the property name as the value if it's resource related
	 *
	 * @param mixed[] $operation
	 * @param string $resourceName Resource name (If it's a resource)
	 * @param mixed[] $resourceProperties Properties (If it's a resource)
	 * @return string[]
	 */
	protected function getRouteOperationInPathParameters($operation, $resourceName = '', $resourceProperties = [])
	{
		$result = [];

		if (isset($operation['parameters'])) {
			foreach ($operation['parameters'] as $parameter) {

				if ($parameter['in'] != 'path') {
					continue;
				}

				$result[$parameter['name']] = null;

				// Check if it's a resource property
				if (count($resourceProperties) > 0) {
					// Resource Id pattern
					$pattern = '/(\w+)Id$/';
					if (preg_match($pattern, $parameter['name'])) {
						$resourcePropertyToMatch = ucfirst(rtrim($parameter['name'], 'Id'));
						$snakeCaseResourcePropertyToMatch = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $resourcePropertyToMatch));
						foreach ($resourceProperties as $resourceProperty) {
							if ($resourceProperty['name'] == $snakeCaseResourcePropertyToMatch) {
								$result[$parameter['name']] = '$this->' . $snakeCaseResourcePropertyToMatch;
							}
						}
					}
				}

				// This resource Id ?
				if (is_null($result[$parameter['name']]) && ($resourceName != '')) {
					// Resource Id pattern
					$pattern = '/(\w+)Id$/';
					if (preg_match($pattern, $parameter['name'])) {
						$resourcePropertyToMatch = ucfirst(rtrim($parameter['name'], 'Id'));
						if ($resourceName == $resourcePropertyToMatch) {
							$result[$parameter['name']] = '$this->id';
						}
					}
				}

				if (is_null($result[$parameter['name']])) {
					$result[$parameter['name']] = '$' . $parameter['name'];
				}
			}
		}

		return $result;
	}

	/**
	 * Return the definition parameters of a resource/manager class method
	 *
	 * @param boolean $inPath Grab parameters "in = path" (only for Managers)
	 * @param string $path
	 * @param string $httpMethod
	 * @param mixed[] $operation
	 * @return mixed[]
	 * @throws Exception
	 */
	protected function getRouteOperationDefinitionParameters($inPath, $path, $httpMethod, $operation)
	{
		$result = [];

		if (isset($operation['parameters'])) {
			foreach ($operation['parameters'] as $parameter) {

				if (!$inPath && $parameter['in'] == 'path') {
					continue;
				}

				$result[$parameter['name']] = [
					'name' => $parameter['name'],
					'required' => $parameter['required'],
				];

				if (isset($parameter['schema'])) {
					if (isset($parameter['schema']['type'])) {
						$result[$parameter['name']]['type'] = $parameter['schema']['type'];
					}

					if (isset($parameter['schema']['format'])) {
						$result[$parameter['name']]['format'] = $parameter['schema']['format'];
					}
				}

				if (isset($parameter['description'])) {
					$result[$parameter['name']]['description'] = $parameter['description'];
				}
			}
		}

		if (isset($operation['requestBody'])) {
			if (isset($operation['requestBody']['$ref'])) {
				// Resolver not supported here
				throw new Exception('Reference object in requestBody is not supported' . "\n" . 'Path: ' . $path . ', HTTP Method: ' . $httpMethod);
			} else {
				if (count($operation['requestBody']['content']) > 0) {
					$firstContentKey = array_keys($operation['requestBody']['content'])[0];
					$firstContent = array_shift($operation['requestBody']['content']);
					$schema = $firstContent['schema'];

					$orderedParameters = [];

					// Place required parameters first
					foreach ($schema['required'] as $required) {
						$orderedParameters[$required] = $schema['properties'][$required];
					}
					foreach ($schema['properties'] as $propertyName => $property) {
						if (!isset($orderedParameters[$propertyName])) {
							$orderedParameters[$propertyName] = $property;
						}
					}

					foreach ($orderedParameters as $parameterName => $parameter) {
						$result[$parameterName] = [
							'name' => $parameterName,
							'required' => in_array($parameterName, $schema['required'])
						];

						if (isset($parameter['type'])) {
							$result[$parameterName]['type'] = $parameter['type'];
						}

						if (isset($parameter['format'])) {
							$result[$parameterName]['format'] = $parameter['format'];
						}

						if (isset($parameter['description'])) {
							$result[$parameterName]['description'] = $parameter['description'];
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Write templates files
	 */
	protected function writeTemplates()
	{
		$this->writeManagersTemplates();
		$this->writeResourcesTemplates();
		$this->writeMainClientTemplate();
		$this->writeMainExceptionTemplate();
	}

	/**
	 * Write main exception template file
	 */
	protected function writeMainExceptionTemplate()
	{
		$data = $this->mainExceptionData;

		$filePath = $this->outputPath . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR . 'ApiException.php';

		if (!is_null($this->outputInterface)) {
			$this->outputInterface->writeln('<info>Writing ' . $filePath . '</info>');
		}

		file_put_contents($filePath, $this->mainExceptionTemplate->render($data));
	}

	/**
	 * Write main client template file
	 */
	protected function writeMainClientTemplate()
	{
		$data = $this->mainClientData;

		$filePath = $this->outputPath . DIRECTORY_SEPARATOR . 'ApiClient.php';

		if (!is_null($this->outputInterface)) {
			$this->outputInterface->writeln('<info>Writing ' . $filePath . '</info>');
		}

		file_put_contents($filePath, $this->mainClientTemplate->render($data));
	}

	/**
	 * Write managers templates files
	 */
	protected function writeManagersTemplates()
	{
		foreach ($this->managersData as $managerName => $managerData) {
			$data = [
				'className' => $managerName . 'Manager',
				'classPhpDocTitle' => $managerName . ' manager class',
				'namespace' => $this->namespace . '\Managers',
				'routes' => $managerData['routes'],
			];

			if (isset($managerData['uses'])) {
				$data['uses'] = $managerData['uses'];
			}

			$filePath = $this->outputPath . DIRECTORY_SEPARATOR . 'Managers' . DIRECTORY_SEPARATOR . $managerName . 'Manager.php';

			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Writing ' . $filePath . '</info>');
			}

			file_put_contents($filePath, $this->managerTemplate->render($data));
		}
	}

	/**
	 * Write resources templates files
	 */
	protected function writeResourcesTemplates()
	{
		foreach ($this->resourcesData as $resourceName => $resourceData) {
			$data = [
				'className' => $resourceName,
				'classPhpDocTitle' => $resourceName . ' resource class',
				'namespace' => $this->namespace . '\Resources',
				'routes' => $resourceData['routes']
			];

			if (isset($resourceData['uses'])) {
				$data['uses'] = $resourceData['uses'];
			}

			if (isset($resourceData['properties'])) {
				$data['properties'] = $resourceData['properties'];
			}

			$filePath = $this->outputPath . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . $resourceName . '.php';

			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Writing ' . $filePath . '</info>');
			}

			file_put_contents($filePath, $this->resourceTemplate->render($data));
		}
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
			'routes' => [],
			'uses' => [
				$this->namespace . '\\ApiClient',
				$this->namespace . '\\Exceptions\\ApiException'
			]
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
			'routes' => [],
			'uses' => [
				$this->namespace . '\\ApiClient',
				$this->namespace . '\\Exceptions\\ApiException'
			]
		];
	}

	/**
	 * Load Twig templates filesystem and custom filters
	 */
	protected function loadTemplates()
	{
		$loader = new \Twig_Loader_Filesystem(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates');
		$this->twigEnv = new Twig_Environment($loader, ['cache' => false, 'debug' => true]);
		$this->twigEnv->addExtension(new Twig_Extension_Debug());

		// Custom filter for phpdoc
		$filter = new \Twig_Filter('phpdoc', function($string, $indentationCount = 0, $indentChar = "\t") {
			$result = str_repeat($indentChar, $indentationCount) . '/**' . "\n";
			// Split per line
			$lines = explode("\n", trim($string));
			foreach ($lines as $line) {
				$result .= str_repeat($indentChar, $indentationCount) . ' * ' . $line . "\n";
			}
			$result .= str_repeat($indentChar, $indentationCount) . ' */' . "\n";
			return $result;
		});

		$this->twigEnv->addFilter($filter);

		$this->managerTemplate = $this->twigEnv->load('manager.php.twig');
		$this->resourceTemplate = $this->twigEnv->load('resource.php.twig');
		$this->mainClientTemplate = $this->twigEnv->load('mainClient.php.twig');
		$this->mainExceptionTemplate = $this->twigEnv->load('mainException.php.twig');
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
		} catch (Exception $e) {
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
				$this->outputInterface->writeln('<info>Main output directory already created (' . $this->outputPath . ')</info>');
			}
		} else {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Making main output directory (' . $this->outputPath . ')</info>');
			}
			mkdir($this->outputPath ,0755, true);
		}

		$managersDirectoryPath = $this->outputPath . DIRECTORY_SEPARATOR . "Managers";
		if (file_exists($managersDirectoryPath)) {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Managers output directory already created (' . $managersDirectoryPath . ')</info>');
			}
		} else {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Making managers output directory (' . $managersDirectoryPath . ')</info>');
			}
			mkdir($managersDirectoryPath ,0755, true);
		}

		$resourcesDirectoryPath = $this->outputPath . DIRECTORY_SEPARATOR . "Resources";
		if (file_exists($resourcesDirectoryPath)) {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Resources output directory already created (' . $resourcesDirectoryPath . ')</info>');
			}
		} else {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Making resources output directory (' . $resourcesDirectoryPath . ')</info>');
			}
			mkdir($resourcesDirectoryPath ,0755, true);
		}

		/*
		$responsesDirectoryPath = $this->outputPath . DIRECTORY_SEPARATOR . "Responses";
		if (file_exists($responsesDirectoryPath)) {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Responses output directory already created (' . $responsesDirectoryPath . ')</info>');
			}
		} else {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Making responses output directory (' . $responsesDirectoryPath . ')</info>');
			}
			mkdir($responsesDirectoryPath ,0755, true);
		}
		*/

		$exceptionsDirectoryPath = $this->outputPath . DIRECTORY_SEPARATOR . "Exceptions";
		if (file_exists($exceptionsDirectoryPath)) {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Exceptions output directory already created (' . $exceptionsDirectoryPath . ')</info>');
			}
		} else {
			if (!is_null($this->outputInterface)) {
				$this->outputInterface->writeln('<info>Making exceptions output directory (' . $exceptionsDirectoryPath . ')</info>');
			}
			mkdir($exceptionsDirectoryPath ,0755, true);
		}
	}
}