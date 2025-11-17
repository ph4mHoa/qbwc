<?php
/**
 * List QBWC Sessions Command
 *
 * Magento-specific command to monitor active sessions
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
use Symfony\Component\Console\Helper\Table;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ListCommand extends Command
{
    /**
     * @var SessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Constructor
     *
     * @param SessionRepositoryInterface $sessionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param string|null $name
     */
    public function __construct(
        SessionRepositoryInterface $sessionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        string $name = null
    ) {
        $this->sessionRepository = $sessionRepository;
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
        $this->setName('qbwc:session:list')
            ->setDescription('List QBWC sessions')
            ->addOption(
                'active',
                'a',
                InputOption::VALUE_NONE,
                'Show only active sessions (progress < 100%)'
            )
            ->addOption(
                'company',
                'c',
                InputOption::VALUE_REQUIRED,
                'Filter by company file'
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
        $activeOnly = $input->getOption('active');
        $company = $input->getOption('company');

        try {
            // Get sessions based on filters
            if ($activeOnly) {
                $sessions = $this->sessionRepository->getActiveSessions();
            } elseif ($company) {
                $sessions = $this->sessionRepository->getByCompany($company);
            } else {
                $searchCriteria = $this->searchCriteriaBuilder->create();
                $sessions = []; // Would need getList in SessionRepository
                // For now, use getActiveSessions as fallback
                $sessions = $this->sessionRepository->getActiveSessions();
            }

            if (empty($sessions)) {
                $output->writeln('<info>No sessions found.</info>');
                return Command::SUCCESS;
            }

            // Display table
            $table = new Table($output);
            $table->setHeaders(['Ticket', 'User', 'Company', 'Progress', 'Current Job', 'Created']);

            foreach ($sessions as $session) {
                $progress = $session->getProgress();
                $progressColor = $progress >= 100 ? 'info' : 'comment';

                $table->addRow([
                    substr($session->getTicket(), 0, 12) . '...',
                    $session->getUser(),
                    $session->getCompany(),
                    "<{$progressColor}>{$progress}%</{$progressColor}>",
                    $session->getCurrentJob() ?: '-',
                    $session->getCreatedAt()
                ]);
            }

            $table->render();

            $output->writeln('');
            $output->writeln(sprintf('<info>Total: %d session(s)</info>', count($sessions)));

            // Show active count
            $activeCount = 0;
            foreach ($sessions as $session) {
                if ($session->getProgress() < 100) {
                    $activeCount++;
                }
            }

            if ($activeCount > 0) {
                $output->writeln(sprintf('<comment>Active: %d session(s)</comment>', $activeCount));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
