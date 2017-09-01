<?php

namespace Rygilles\OpenApiPhpClientGenerator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rygilles\OpenApiGenerator\Generator;


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
			->setDescription('Generate PHP client files.');
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
		$outputPath = '';
		$options = [];

		$generator = new Generator($openApiFilePath, $outputPath, $options);

		$output->writeln('<info>Test...</info>');
	}
}