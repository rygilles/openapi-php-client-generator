<?php

namespace Rygilles\OpenApiPhpClientGenerator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rygilles\OpenApiPhpClientGenerator\Generator;


class GenerateCommand extends Command
{
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('generate')
			->setDescription('Generate PHP client files.')
			->addArgument('source', InputArgument::REQUIRED, 'The OpenAPI file path')
			->addArgument('output', InputArgument::REQUIRED, 'The output folder path')
			->addArgument('namespace', InputArgument::REQUIRED, 'The base namespace of PHP generated files')
			->addArgument('options', InputArgument::OPTIONAL, 'Options');
	}

	/**
	 * Execute the command.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$openApiFilePath = $input->getArgument('source');
		$outputPath = $input->getArgument('output');
		$namespace = $input->getArgument('namespace');
		$options = $input->getArgument('options');

		$generator = new Generator($openApiFilePath, $outputPath, $namespace, $options, $output);
		$generator->generate();

		$output->writeln('<info>Generation complete</info>');
	}
}