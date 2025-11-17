<?php
/**
 * Invoice Sync CLI Command
 *
 * Usage: bin/magento sample:qb:invoice:sync [company-file] [--from=YYYY-MM-DD] [--to=YYYY-MM-DD]
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

class InvoiceSyncCommand extends Command
{
    const ARGUMENT_COMPANY = 'company';
    const OPTION_FROM = 'from';
    const OPTION_TO = 'to';
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
        $this->setName('sample:qb:invoice:sync')
            ->setDescription('Create an invoice sync job for QuickBooks')
            ->addArgument(
                self::ARGUMENT_COMPANY,
                InputArgument::OPTIONAL,
                'QuickBooks company file path',
                'C:\\QuickBooks\\company.qbw'
            )
            ->addOption(
                self::OPTION_FROM,
                'f',
                InputOption::VALUE_REQUIRED,
                'Start date (YYYY-MM-DD)'
            )
            ->addOption(
                self::OPTION_TO,
                't',
                InputOption::VALUE_REQUIRED,
                'End date (YYYY-MM-DD)'
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
        $dateFrom = $input->getOption(self::OPTION_FROM);
        $dateTo = $input->getOption(self::OPTION_TO);
        $enabled = !$input->getOption(self::OPTION_DISABLE);

        $output->writeln('<info>Creating invoice sync job...</info>');
        $output->writeln("Company: {$companyFile}");
        $output->writeln("Date From: " . ($dateFrom ?: 'All'));
        $output->writeln("Date To: " . ($dateTo ?: 'All'));
        $output->writeln("Enabled: " . ($enabled ? 'Yes' : 'No'));

        try {
            $job = $this->jobManager->createInvoiceSyncJob($companyFile, $enabled, $dateFrom, $dateTo);

            $output->writeln('<info>Success!</info>');
            $output->writeln("Job ID: {$job->getEntityId()}");
            $output->writeln("Job Name: {$job->getName()}");
            $output->writeln("Worker: {$job->getWorkerClass()}");

            if ($dateFrom || $dateTo) {
                $output->writeln('');
                $output->writeln('<comment>Date Range Filter:</comment>');
                $output->writeln("From: " . ($dateFrom ?: 'Beginning'));
                $output->writeln("To: " . ($dateTo ?: 'Now'));
            }

            $output->writeln('');
            $output->writeln('<comment>Note:</comment>');
            $output->writeln('This job will only run during non-business hours (9 PM - 6 AM)');
            $output->writeln('See InvoiceSyncWorker::shouldRun() to modify this behavior');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
