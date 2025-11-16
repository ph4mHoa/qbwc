<?php
/**
 * Session Model
 *
 * Cloned from QBWC Rails gem - lib/qbwc/session.rb
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Magento\Framework\Model\AbstractModel;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class Session extends AbstractModel implements SessionInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param SessionResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        SessionResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SessionResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        return $this->getData(self::ENTITY_ID) ? (int) $this->getData(self::ENTITY_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setEntityId(int $entityId): SessionInterface
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getTicket(): string
    {
        return (string) $this->getData(self::TICKET);
    }

    /**
     * @inheritDoc
     */
    public function setTicket(string $ticket): SessionInterface
    {
        return $this->setData(self::TICKET, $ticket);
    }

    /**
     * @inheritDoc
     */
    public function getUser(): string
    {
        return (string) $this->getData(self::USER);
    }

    /**
     * @inheritDoc
     */
    public function setUser(string $user): SessionInterface
    {
        return $this->setData(self::USER, $user);
    }

    /**
     * @inheritDoc
     */
    public function getCompany(): string
    {
        return (string) $this->getData(self::COMPANY);
    }

    /**
     * @inheritDoc
     */
    public function setCompany(string $company): SessionInterface
    {
        return $this->setData(self::COMPANY, $company);
    }

    /**
     * @inheritDoc
     */
    public function getProgress(): int
    {
        return (int) $this->getData(self::PROGRESS);
    }

    /**
     * @inheritDoc
     */
    public function setProgress(int $progress): SessionInterface
    {
        return $this->setData(self::PROGRESS, $progress);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentJob(): ?string
    {
        return $this->getData(self::CURRENT_JOB);
    }

    /**
     * @inheritDoc
     */
    public function setCurrentJob(?string $jobName): SessionInterface
    {
        return $this->setData(self::CURRENT_JOB, $jobName);
    }

    /**
     * @inheritDoc
     */
    public function getPendingJobs(): ?string
    {
        return $this->getData(self::PENDING_JOBS);
    }

    /**
     * @inheritDoc
     */
    public function setPendingJobs(?string $jobs): SessionInterface
    {
        return $this->setData(self::PENDING_JOBS, $jobs);
    }

    /**
     * Get pending jobs as array
     *
     * @return array
     */
    public function getPendingJobsArray(): array
    {
        $jobs = $this->getPendingJobs();
        if (empty($jobs)) {
            return [];
        }
        try {
            return $this->serializer->unserialize($jobs);
        } catch (\Exception $e) {
            $this->logger->error('Failed to unserialize pending jobs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set pending jobs from array
     *
     * @param array $jobs
     * @return $this
     */
    public function setPendingJobsArray(array $jobs): self
    {
        try {
            $serialized = $this->serializer->serialize($jobs);
            return $this->setPendingJobs($serialized);
        } catch (\Exception $e) {
            $this->logger->error('Failed to serialize pending jobs: ' . $e->getMessage());
            return $this;
        }
    }

    /**
     * @inheritDoc
     */
    public function getIteratorId(): ?string
    {
        return $this->getData(self::ITERATOR_ID);
    }

    /**
     * @inheritDoc
     */
    public function setIteratorId(?string $iteratorId): SessionInterface
    {
        return $this->setData(self::ITERATOR_ID, $iteratorId);
    }

    /**
     * @inheritDoc
     */
    public function getError(): ?string
    {
        return $this->getData(self::ERROR);
    }

    /**
     * @inheritDoc
     */
    public function setError(?string $error): SessionInterface
    {
        return $this->setData(self::ERROR, $error);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): ?string
    {
        return $this->getData(self::STATUS_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setStatusCode(?string $code): SessionInterface
    {
        return $this->setData(self::STATUS_CODE, $code);
    }

    /**
     * @inheritDoc
     */
    public function getStatusSeverity(): ?string
    {
        return $this->getData(self::STATUS_SEVERITY);
    }

    /**
     * @inheritDoc
     */
    public function setStatusSeverity(?string $severity): SessionInterface
    {
        return $this->setData(self::STATUS_SEVERITY, $severity);
    }

    /**
     * @inheritDoc
     */
    public function getInitialJobCount(): int
    {
        return (int) $this->getData(self::INITIAL_JOB_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setInitialJobCount(int $count): SessionInterface
    {
        return $this->setData(self::INITIAL_JOB_COUNT, $count);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): SessionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): SessionInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Calculate progress percentage
     *
     * Cloned from Rails: lib/qbwc/session.rb:53-55
     *
     * @return $this
     */
    public function calculateProgress(): self
    {
        $initialCount = $this->getInitialJobCount();
        $pendingJobs = $this->getPendingJobsArray();
        $currentCount = count($pendingJobs);

        if ($initialCount > 0) {
            $jobsCompleted = $initialCount - $currentCount;
            $progress = (int) (($jobsCompleted / $initialCount) * 100);
            $this->setProgress($progress);
        }

        return $this;
    }

    /**
     * Check if session has error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->getError());
    }

    /**
     * Check if response is error
     *
     * Cloned from Rails: lib/qbwc/session.rb:31-33
     *
     * @return bool
     */
    public function responseIsError(): bool
    {
        return $this->hasError() && $this->getStatusSeverity() === 'Error';
    }

    /**
     * Check if session is completed
     *
     * Cloned from Rails: lib/qbwc/session.rb:39-41
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getProgress() >= 100;
    }

    /**
     * Generate unique ticket
     *
     * Cloned from Rails: lib/qbwc/session.rb:21
     * Original uses SHA1, we use SHA256 for better security
     *
     * @param string $username
     * @param string $company
     * @return string
     */
    public static function generateTicket(string $username, string $company): string
    {
        return hash('sha256', uniqid($username . $company, true));
    }

    /**
     * Get session key (user + company)
     *
     * Cloned from Rails: lib/qbwc/session.rb:27-29
     *
     * @return array
     */
    public function getKey(): array
    {
        return [$this->getUser(), $this->getCompany()];
    }

    /**
     * Check if should stop on error
     *
     * Cloned from Rails: lib/qbwc/session.rb:35-37
     *
     * @param string $onErrorConfig
     * @return bool
     */
    public function shouldStopOnError(string $onErrorConfig): bool
    {
        return $this->responseIsError() && $onErrorConfig === 'stopOnError';
    }
}
