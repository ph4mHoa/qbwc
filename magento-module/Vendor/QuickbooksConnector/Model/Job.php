<?php
/**
 * Job Model
 *
 * Cloned from QBWC Rails gem - lib/qbwc/job.rb
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Magento\Framework\Model\AbstractModel;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job as JobResource;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

class Job extends AbstractModel implements JobInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

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
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param JobResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        SerializerInterface $serializer,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        JobResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->objectManager = $objectManager;
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
        $this->_init(JobResource::class);
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
    public function setEntityId(int $entityId): JobInterface
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): JobInterface
    {
        return $this->setData(self::NAME, $name);
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
    public function setCompany(string $company): JobInterface
    {
        return $this->setData(self::COMPANY, $company);
    }

    /**
     * @inheritDoc
     */
    public function getWorkerClass(): string
    {
        return (string) $this->getData(self::WORKER_CLASS);
    }

    /**
     * @inheritDoc
     */
    public function setWorkerClass(string $workerClass): JobInterface
    {
        return $this->setData(self::WORKER_CLASS, $workerClass);
    }

    /**
     * @inheritDoc
     */
    public function getEnabled(): bool
    {
        return (bool) $this->getData(self::ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function setEnabled(bool $enabled): JobInterface
    {
        return $this->setData(self::ENABLED, $enabled);
    }

    /**
     * @inheritDoc
     */
    public function getRequestIndex(): ?string
    {
        return $this->getData(self::REQUEST_INDEX);
    }

    /**
     * @inheritDoc
     */
    public function setRequestIndex(?string $requestIndex): JobInterface
    {
        return $this->setData(self::REQUEST_INDEX, $requestIndex);
    }

    /**
     * @inheritDoc
     */
    public function getRequests(): ?string
    {
        return $this->getData(self::REQUESTS);
    }

    /**
     * @inheritDoc
     */
    public function setRequests(?string $requests): JobInterface
    {
        return $this->setData(self::REQUESTS, $requests);
    }

    /**
     * @inheritDoc
     */
    public function getRequestsProvidedWhenJobAdded(): bool
    {
        return (bool) $this->getData(self::REQUESTS_PROVIDED_WHEN_JOB_ADDED);
    }

    /**
     * @inheritDoc
     */
    public function setRequestsProvidedWhenJobAdded(bool $flag): JobInterface
    {
        return $this->setData(self::REQUESTS_PROVIDED_WHEN_JOB_ADDED, $flag);
    }

    /**
     * @inheritDoc
     */
    public function getData($key = '', $index = null)
    {
        // Special handling for 'data' field to avoid conflict with getData method
        if ($key === 'data' || $key === self::DATA) {
            return parent::getData(self::DATA);
        }
        return parent::getData($key, $index);
    }

    /**
     * @inheritDoc
     */
    public function setData($key, $value = null)
    {
        // Special handling for 'data' field
        if ($key === 'data' && is_string($value)) {
            return parent::setData(self::DATA, $value);
        }
        return parent::setData($key, $value);
    }

    /**
     * Get job data (decoded from JSON)
     *
     * @return array
     */
    public function getJobData(): array
    {
        $data = parent::getData(self::DATA);
        if (empty($data)) {
            return [];
        }
        try {
            return $this->serializer->unserialize($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to unserialize job data: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set job data (encode to JSON)
     *
     * @param array $data
     * @return $this
     */
    public function setJobData(array $data): self
    {
        try {
            $serialized = $this->serializer->serialize($data);
            parent::setData(self::DATA, $serialized);
        } catch (\Exception $e) {
            $this->logger->error('Failed to serialize job data: ' . $e->getMessage());
        }
        return $this;
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
    public function getUpdatedAt(): string
    {
        return (string) $this->getData(self::UPDATED_AT);
    }

    /**
     * Get worker instance
     *
     * Cloned from Rails: lib/qbwc/job.rb:20-22
     *
     * @return \Vendor\QuickbooksConnector\Model\Worker\AbstractWorker
     * @throws \Exception
     */
    public function getWorker()
    {
        $workerClass = $this->getWorkerClass();
        if (empty($workerClass)) {
            throw new \Exception('Worker class not defined for job: ' . $this->getName());
        }

        try {
            return $this->objectManager->create($workerClass);
        } catch (\Exception $e) {
            throw new \Exception(
                'Failed to create worker: ' . $workerClass . '. Error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get request index for session
     *
     * Cloned from Rails: lib/qbwc/job.rb:88-90
     *
     * @param array $sessionKey
     * @return int
     */
    public function getRequestIndexForSession(array $sessionKey): int
    {
        $indexData = $this->getRequestIndex();
        if (empty($indexData)) {
            return 0;
        }

        try {
            $indices = $this->serializer->unserialize($indexData);
            $key = implode('_', $sessionKey);
            return isset($indices[$key]) ? (int) $indices[$key] : 0;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get request index: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Set request index for session
     *
     * @param array $sessionKey
     * @param int $index
     * @return $this
     */
    public function setRequestIndexForSession(array $sessionKey, int $index): self
    {
        $indexData = $this->getRequestIndex();

        try {
            $indices = empty($indexData) ? [] : $this->serializer->unserialize($indexData);
            $key = implode('_', $sessionKey);
            $indices[$key] = $index;
            $this->setRequestIndex($this->serializer->serialize($indices));
        } catch (\Exception $e) {
            $this->logger->error('Failed to set request index: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Get requests for session
     *
     * Cloned from Rails: lib/qbwc/job.rb:61-69
     *
     * @param array $sessionKey
     * @return array
     */
    public function getRequestsForSession(array $sessionKey): array
    {
        $requestsData = $this->getRequests();
        if (empty($requestsData)) {
            return [];
        }

        try {
            $allRequests = $this->serializer->unserialize($requestsData);
            $key = implode('_', $sessionKey);

            // Try session-specific key first, then fall back to default
            if (isset($allRequests[$key])) {
                return $allRequests[$key];
            }

            // Try with nil username (secondary key)
            $secondaryKey = '_' . $sessionKey[1];
            if (isset($allRequests[$secondaryKey])) {
                return $allRequests[$secondaryKey];
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get requests for session: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set requests for session
     *
     * Cloned from Rails: lib/qbwc/job.rb:71-74
     *
     * @param array $sessionKey
     * @param array $requests
     * @return $this
     */
    public function setRequestsForSession(array $sessionKey, array $requests): self
    {
        $requestsData = $this->getRequests();

        try {
            $allRequests = empty($requestsData) ? [] : $this->serializer->unserialize($requestsData);
            $key = implode('_', $sessionKey);
            $allRequests[$key] = $requests;
            $this->setRequests($this->serializer->serialize($allRequests));
        } catch (\Exception $e) {
            $this->logger->error('Failed to set requests for session: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Advance to next request
     *
     * Cloned from Rails: lib/qbwc/job.rb:33-37
     *
     * @param array $sessionKey
     * @return $this
     */
    public function advanceNextRequest(array $sessionKey): self
    {
        $currentIndex = $this->getRequestIndexForSession($sessionKey);
        $newIndex = $currentIndex + 1;
        $this->setRequestIndexForSession($sessionKey, $newIndex);

        $this->logger->info(
            "Job '{$this->getName()}' advancing to request #{$newIndex}"
        );

        return $this;
    }

    /**
     * Reset job state
     *
     * Cloned from Rails: lib/qbwc/job.rb:121-124
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->setRequestIndex(null);

        if (!$this->getRequestsProvidedWhenJobAdded()) {
            $this->setRequests(null);
        }

        return $this;
    }

    /**
     * Enable job
     *
     * Cloned from Rails: lib/qbwc/job.rb:39-41
     *
     * @return $this
     */
    public function enable(): self
    {
        return $this->setEnabled(true);
    }

    /**
     * Disable job
     *
     * Cloned from Rails: lib/qbwc/job.rb:43-45
     *
     * @return $this
     */
    public function disable(): self
    {
        return $this->setEnabled(false);
    }

    /**
     * Check if job is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getEnabled();
    }
}
