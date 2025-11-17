<?php
/**
 * Job Create CLI Command - Interactive job creation
 *
 * Usage: bin/magento sample:qb:job:create
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Sample\QuickbooksDemo\Api\JobManagerInterface;

class JobCreateCommand extends Command
{
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
        $this->setName('sample:qb:job:create')
            ->setDescription('Create a QuickBooks job interactively');

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
        $output->writeln('<info>QuickBooks Job Creator</info>');
        $output->writeln('');

        $helper = $this->getHelper('question');

        // Ask for job type
        $typeQuestion = new ChoiceQuestion(
            'Select job type:',
            [
                '1' => 'Customer Sync',
                '2' => 'Invoice Sync',
                '3' => 'Product Query'
            ],
            '1'
        );
        $typeQuestion->setErrorMessage('Invalid choice %s');
        $jobType = $helper->ask($input, $output, $typeQuestion);

        // Ask for company file
        $companyQuestion = new Question(
            'QuickBooks company file path [C:\\QuickBooks\\company.qbw]: ',
            'C:\\QuickBooks\\company.qbw'
        );
        $companyFile = $helper->ask($input, $output, $companyQuestion);

        // Ask if enabled
        $enabledQuestion = new ConfirmationQuestion('Enable job immediately? [Y/n]: ', true);
        $enabled = $helper->ask($input, $output, $enabledQuestion);

        try {
            $output->writeln('');
            $output->writeln('<info>Creating job...</info>');

            $job = null;

            switch ($jobType) {
                case 'Customer Sync':
                    $job = $this->jobManager->createCustomerSyncJob($companyFile, $enabled);
                    break;

                case 'Invoice Sync':
                    // Ask for date range
                    $fromQuestion = new Question('Start date (YYYY-MM-DD) [optional]: ');
                    $dateFrom = $helper->ask($input, $output, $fromQuestion);

                    $toQuestion = new Question('End date (YYYY-MM-DD) [optional]: ');
                    $dateTo = $helper->ask($input, $output, $toQuestion);

                    $job = $this->jobManager->createInvoiceSyncJob(
                        $companyFile,
                        $enabled,
                        $dateFrom ?: null,
                        $dateTo ?: null
                    );
                    break;

                case 'Product Query':
                    // Ask if force
                    $forceQuestion = new ConfirmationQuestion('Force run on weekdays? [y/N]: ', false);
                    $force = $helper->ask($input, $output, $forceQuestion);

                    $job = $this->jobManager->createProductQueryJob($companyFile, $enabled, $force);
                    break;
            }

            if ($job) {
                $output->writeln('');
                $output->writeln('<info>Job created successfully!</info>');
                $output->writeln('');
                $output->writeln("Job ID: {$job->getEntityId()}");
                $output->writeln("Job Name: {$job->getName()}");
                $output->writeln("Company: {$job->getCompany()}");
                $output->writeln("Worker: {$job->getWorkerClass()}");
                $output->writeln("Enabled: " . ($job->getEnabled() ? 'Yes' : 'No'));
                $output->writeln('');
                $output->writeln('<comment>Next steps:</comment>');
                $output->writeln('1. Configure QBWC to connect to your Magento instance');
                $output->writeln('2. Run update in QBWC to execute this job');
                $output->writeln('3. Use: bin/magento sample:qb:job:list to view all jobs');

                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::FAILURE;
    }
}
