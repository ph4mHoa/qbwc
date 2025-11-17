<?php
/**
 * Cleanup Old QBWC Sessions Command
 *
 * Magento-specific command to cleanup old completed sessions
 * Useful for maintenance and keeping database clean
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Console\Command\Session;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;

class CleanupCommand extends Command
{
    /**
     * @var SessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * Constructor
     *
     * @param SessionRepositoryInterface $sessionRepository
     * @param string|null $name
     */
    public function __construct(
        SessionRepositoryInterface $sessionRepository,
        string $name = null
    ) {
        $this->sessionRepository = $sessionRepository;
        parent::__construct($name);
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('qbwc:session:cleanup')
            ->setDescription('Delete old QBWC sessions')
            ->addOption(
                'hours',
                null,
                InputOption::VALUE_REQUIRED,
                'Delete sessions older than specified hours',
                '24'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be deleted without actually deleting'
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
        $hours = (int) $input->getOption('hours');
        $dryRun = $input->getOption('dry-run');

        if ($hours < 1) {
            $output->writeln('<error>Hours must be at least 1.</error>');
            return Command::FAILURE;
        }

        try {
            if ($dryRun) {
                $output->writeln("<comment>DRY RUN: Would delete sessions older than {$hours} hours</comment>");
                // In dry-run mode, just show count
                $count = $this->sessionRepository->getActiveSessionsCount();
                $output->writeln("<info>Currently {$count} active sessions in database.</info>");
                $output->writeln('<comment>Re-run without --dry-run to actually delete.</comment>');
                return Command::SUCCESS;
            }

            $output->writeln("<info>Deleting sessions older than {$hours} hours...</info>");

            $deletedCount = $this->sessionRepository->deleteOldSessions($hours);

            if ($deletedCount > 0) {
                $output->writeln("<info>Successfully deleted {$deletedCount} session(s).</info>");
            } else {
                $output->writeln('<comment>No old sessions found to delete.</comment>');
            }

            // Show remaining sessions
            $remainingCount = $this->sessionRepository->getActiveSessionsCount();
            $output->writeln("<info>Remaining active sessions: {$remainingCount}</info>");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
