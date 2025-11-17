<?php
/**
 * Product Query CLI Command
 *
 * Usage: bin/magento sample:qb:product:query [company-file] [--force]
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sample\QuickbooksDemo\Api\JobManagerInterface;

class ProductQueryCommand extends Command
{
    const ARGUMENT_COMPANY = 'company';
    const OPTION_FORCE = 'force';
    const OPTION_DISABLE = 'disable';

    /**
     * @var JobManagerInterface
     */
    private $jobManager;

    /**
     * Constructor
     *
     * @param JobManagerInterface $jobManager
     * @param string|null $name
     */
    public function __construct(
        JobManagerInterface $jobManager,
        string $name = null
    ) {
        $this->jobManager = $jobManager;
        parent::__construct($name);
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('sample:qb:product:query')
            ->setDescription('Create a product query job for QuickBooks')
            ->addArgument(
                self::ARGUMENT_COMPANY,
                InputArgument::OPTIONAL,
                'QuickBooks company file path',
                'C:\\QuickBooks\\company.qbw'
            )
            ->addOption(
                self::OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Force run even on weekdays'
            )
            ->addOption(
                self::OPTION_DISABLE,
                'd',
                InputOption::VALUE_NONE,
                'Create job in disabled state'
            );

        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $companyFile = $input->getArgument(self::ARGUMENT_COMPANY);
        $force = $input->getOption(self::OPTION_FORCE);
        $enabled = !$input->getOption(self::OPTION_DISABLE);

        $output->writeln('<info>Creating product query job...</info>');
        $output->writeln("Company: {$companyFile}");
        $output->writeln("Force Run: " . ($force ? 'Yes' : 'No'));
        $output->writeln("Enabled: " . ($enabled ? 'Yes' : 'No'));

        try {
            $job = $this->jobManager->createProductQueryJob($companyFile, $enabled, $force);

            $output->writeln('<info>Success!</info>');
            $output->writeln("Job ID: {$job->getEntityId()}");
            $output->writeln("Job Name: {$job->getName()}");
            $output->writeln("Worker: {$job->getWorkerClass()}");

            $output->writeln('');
            $output->writeln('<comment>Note:</comment>');
            if ($force) {
                $output->writeln('Job will run any day (force=true)');
            } else {
                $output->writeln('Job will only run on weekends (Sat/Sun)');
                $output->writeln('Use --force to run on weekdays');
            }

            $output->writeln('');
            $output->writeln('<comment>This job will query:</comment>');
            $output->writeln('- Inventory Items (products with qty tracking)');
            $output->writeln('- Service Items (virtual products)');
            $output->writeln('- Non-Inventory Items (simple products)');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
