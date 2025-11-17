<?php
/**
 * List QBWC Jobs Command
 *
 * Magento CLI equivalent of Rails: QBWC.jobs
 * Rails source: lib/qbwc.rb:82-84
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Console\Command\Job;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ListCommand extends Command
{
    /**
     * @var JobRepositoryInterface
     */
    private $jobRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Constructor
     *
     * @param JobRepositoryInterface $jobRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param string|null $name
     */
    public function __construct(
        JobRepositoryInterface $jobRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        string $name = null
    ) {
        $this->jobRepository = $jobRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($name);
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('qbwc:job:list')
            ->setDescription('List all QBWC jobs')
            ->addOption(
                'company',
                'c',
                InputOption::VALUE_REQUIRED,
                'Filter by company file'
            )
            ->addOption(
                'enabled',
                'e',
                InputOption::VALUE_NONE,
                'Show only enabled jobs'
            );

        parent::configure();
    }

    /**
     * Execute command
     *
     * Rails equivalent: QBWC.jobs
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company = $input->getOption('company');
        $enabledOnly = $input->getOption('enabled');

        try {
            // Get jobs based on filters
            if ($company) {
                $jobs = $this->jobRepository->getByCompany($company);
            } else {
                $searchCriteria = $this->searchCriteriaBuilder->create();
                $jobs = $this->jobRepository->getList($searchCriteria)->getItems();
            }

            // Filter enabled if requested
            if ($enabledOnly) {
                $jobs = array_filter($jobs, function ($job) {
                    return $job->getEnabled();
                });
            }

            if (empty($jobs)) {
                $output->writeln('<info>No jobs found.</info>');
                return Command::SUCCESS;
            }

            // Display table
            $table = new Table($output);
            $table->setHeaders(['ID', 'Name', 'Company', 'Enabled', 'Worker Class', 'Created']);

            foreach ($jobs as $job) {
                $table->addRow([
                    $job->getEntityId(),
                    $job->getName(),
                    $job->getCompany() ?: '(any)',
                    $job->getEnabled() ? '<info>Yes</info>' : '<comment>No</comment>',
                    $job->getWorkerClass(),
                    $job->getCreatedAt()
                ]);
            }

            $table->render();

            $output->writeln('');
            $output->writeln(sprintf('<info>Total: %d job(s)</info>', count($jobs)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
