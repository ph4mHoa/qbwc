<?php
/**
 * Create QBWC Job Command
 *
 * Magento CLI equivalent of Rails: QBWC.add_job(name, enabled, company, klass)
 * Rails source: lib/qbwc.rb:86-88
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Console\Command\Job;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterfaceFactory;

class CreateCommand extends Command
{
    /**
     * @var JobRepositoryInterface
     */
    private $jobRepository;

    /**
     * @var JobInterfaceFactory
     */
    private $jobFactory;

    /**
     * Constructor
     *
     * @param JobRepositoryInterface $jobRepository
     * @param JobInterfaceFactory $jobFactory
     * @param string|null $name
     */
    public function __construct(
        JobRepositoryInterface $jobRepository,
        JobInterfaceFactory $jobFactory,
        string $name = null
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobFactory = $jobFactory;
        parent::__construct($name);
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('qbwc:job:create')
            ->setDescription('Create a new QBWC job')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Job name (unique identifier)'
            )
            ->addArgument(
                'worker-class',
                InputArgument::REQUIRED,
                'Worker class (must extend AbstractWorker)'
            )
            ->addOption(
                'company',
                'c',
                InputOption::VALUE_REQUIRED,
                'Company file path (leave empty for any open file)',
                ''
            )
            ->addOption(
                'enabled',
                'e',
                InputOption::VALUE_REQUIRED,
                'Enable job (1=yes, 0=no)',
                '1'
            );

        parent::configure();
    }

    /**
     * Execute command
     *
     * Rails equivalent: QBWC.add_job(name, enabled, company, klass)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $workerClass = $input->getArgument('worker-class');
        $company = $input->getOption('company');
        $enabled = (bool) $input->getOption('enabled');

        try {
            // Validate job doesn't already exist
            if ($this->jobRepository->jobExists($name)) {
                $output->writeln("<error>Job '{$name}' already exists.</error>");
                return Command::FAILURE;
            }

            // Validate worker class exists
            if (!class_exists($workerClass)) {
                $output->writeln("<error>Worker class '{$workerClass}' does not exist.</error>");
                return Command::FAILURE;
            }

            // Create job
            $job = $this->jobFactory->create();
            $job->setName($name);
            $job->setWorkerClass($workerClass);
            $job->setCompany($company);
            $job->setEnabled($enabled);
            $job->setRequestsProvidedWhenJobAdded(false);

            // Save
            $this->jobRepository->save($job);

            $output->writeln("<info>Job '{$name}' created successfully.</info>");
            $output->writeln('');
            $output->writeln('Details:');
            $output->writeln("  ID:           {$job->getEntityId()}");
            $output->writeln("  Name:         {$name}");
            $output->writeln("  Worker:       {$workerClass}");
            $output->writeln("  Company:      " . ($company ?: '(any)'));
            $output->writeln("  Enabled:      " . ($enabled ? 'Yes' : 'No'));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
