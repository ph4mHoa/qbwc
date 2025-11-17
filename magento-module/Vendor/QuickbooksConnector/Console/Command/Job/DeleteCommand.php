<?php
/**
 * Delete QBWC Job Command
 *
 * Magento CLI equivalent of Rails: QBWC.delete_job(name)
 * Rails source: lib/qbwc.rb:94-97
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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;

class DeleteCommand extends Command
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
        $this->setName('qbwc:job:delete')
            ->setDescription('Delete a QBWC job')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Job name to delete'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force delete without confirmation'
            );

        parent::configure();
    }

    /**
     * Execute command
     *
     * Rails equivalent: QBWC.delete_job(name) (lib/qbwc.rb:94-97)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $force = $input->getOption('force');

        try {
            // Check if job exists
            if (!$this->jobRepository->jobExists($name)) {
                $output->writeln("<error>Job '{$name}' not found.</error>");
                return Command::FAILURE;
            }

            // Confirmation (unless --force)
            if (!$force) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    "<question>Are you sure you want to delete job '{$name}'? [y/N]</question> ",
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln('<comment>Deletion cancelled.</comment>');
                    return Command::SUCCESS;
                }
            }

            // Delete job
            $this->jobRepository->deleteByName($name);

            $output->writeln("<info>Job '{$name}' deleted successfully.</info>");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
