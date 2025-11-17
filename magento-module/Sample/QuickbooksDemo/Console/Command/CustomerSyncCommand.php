<?php
/**
 * Customer Sync CLI Command
 *
 * Usage: bin/magento sample:qb:customer:sync [company-file]
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

class CustomerSyncCommand extends Command
{
    const ARGUMENT_COMPANY = 'company';
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
        $this->setName('sample:qb:customer:sync')
            ->setDescription('Create a customer sync job for QuickBooks')
            ->addArgument(
                self::ARGUMENT_COMPANY,
                InputArgument::OPTIONAL,
                'QuickBooks company file path',
                'C:\\QuickBooks\\company.qbw'
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
        $enabled = !$input->getOption(self::OPTION_DISABLE);

        $output->writeln('<info>Creating customer sync job...</info>');
        $output->writeln("Company: {$companyFile}");
        $output->writeln("Enabled: " . ($enabled ? 'Yes' : 'No'));

        try {
            $job = $this->jobManager->createCustomerSyncJob($companyFile, $enabled);

            $output->writeln('<info>Success!</info>');
            $output->writeln("Job ID: {$job->getEntityId()}");
            $output->writeln("Job Name: {$job->getName()}");
            $output->writeln("Worker: {$job->getWorkerClass()}");
            $output->writeln('');
            $output->writeln('<comment>Next steps:</comment>');
            $output->writeln('1. Configure QuickBooks Web Connector to connect to your Magento instance');
            $output->writeln('2. Run update in QBWC to execute this job');
            $output->writeln('3. Monitor logs: var/log/quickbooks_demo.log');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
