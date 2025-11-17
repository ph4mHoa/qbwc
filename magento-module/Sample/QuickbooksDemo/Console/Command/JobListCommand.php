<?php
/**
 * Job List CLI Command
 *
 * Usage: bin/magento sample:qb:job:list [--company=path]
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Sample\QuickbooksDemo\Api\JobManagerInterface;

class JobListCommand extends Command
{
    const OPTION_COMPANY = 'company';

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
        $this->setName('sample:qb:job:list')
            ->setDescription('List all QuickBooks jobs')
            ->addOption(
                self::OPTION_COMPANY,
                'c',
                InputOption::VALUE_REQUIRED,
                'Filter by company file path'
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
        $companyFile = $input->getOption(self::OPTION_COMPANY);

        try {
            if ($companyFile) {
                $output->writeln("<info>Listing jobs for: {$companyFile}</info>");
                $jobs = $this->jobManager->getJobsByCompany($companyFile);
            } else {
                $output->writeln('<info>Listing all QuickBooks jobs</info>');
                $jobs = $this->jobManager->listAllJobs();
            }

            if (empty($jobs)) {
                $output->writeln('<comment>No jobs found.</comment>');
                return Command::SUCCESS;
            }

            // Create table
            $table = new Table($output);
            $table->setHeaders(['ID', 'Name', 'Company', 'Worker', 'Enabled', 'Created']);

            foreach ($jobs as $job) {
                $workerClass = $job->getWorkerClass();
                $workerShort = substr($workerClass, strrpos($workerClass, '\\') + 1);

                $table->addRow([
                    $job->getEntityId(),
                    $job->getName(),
                    basename($job->getCompany()),
                    $workerShort,
                    $job->getEnabled() ? 'Yes' : 'No',
                    $job->getCreatedAt()
                ]);
            }

            $table->render();

            $output->writeln('');
            $output->writeln('<comment>Total: ' . count($jobs) . ' job(s)</comment>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
