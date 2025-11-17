<?php
/**
 * Enable QBWC Job Command
 *
 * Magento CLI equivalent of Rails: job.enable
 * Rails source: lib/qbwc/job.rb:39-41
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Console\Command\Job;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;

class EnableCommand extends Command
{
    /**
     * @var JobRepositoryInterface
     */
    private $jobRepository;

    /**
     * Constructor
     *
     * @param JobRepositoryInterface $jobRepository
     * @param string|null $name
     */
    public function __construct(
        JobRepositoryInterface $jobRepository,
        string $name = null
    ) {
        $this->jobRepository = $jobRepository;
        parent::__construct($name);
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('qbwc:job:enable')
            ->setDescription('Enable a QBWC job')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Job name to enable'
            );

        parent::configure();
    }

    /**
     * Execute command
     *
     * Rails equivalent: job.enable (lib/qbwc/job.rb:39-41)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {
            // Check if job exists
            if (!$this->jobRepository->jobExists($name)) {
                $output->writeln("<error>Job '{$name}' not found.</error>");
                return Command::FAILURE;
            }

            // Enable job
            $result = $this->jobRepository->enableJob($name);

            if ($result) {
                $output->writeln("<info>Job '{$name}' enabled successfully.</info>");
                return Command::SUCCESS;
            } else {
                $output->writeln("<comment>Job '{$name}' is already enabled.</comment>");
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
