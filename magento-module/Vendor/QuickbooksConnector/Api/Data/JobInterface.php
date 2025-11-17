<?php
/**
 * Job Data Interface
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api\Data;

interface JobInterface
{
    const ENTITY_ID = 'entity_id';
    const NAME = 'name';
    const COMPANY = 'company';
    const WORKER_CLASS = 'worker_class';
    const ENABLED = 'enabled';
    const REQUEST_INDEX = 'request_index';
    const REQUESTS = 'requests';
    const REQUESTS_PROVIDED_WHEN_JOB_ADDED = 'requests_provided_when_job_added';
    const DATA = 'data';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId): JobInterface;

    /**
     * Get job name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set job name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): JobInterface;

    /**
     * Get company file path
     *
     * @return string
     */
    public function getCompany(): string;

    /**
     * Set company file path
     *
     * @param string $company
     * @return $this
     */
    public function setCompany(string $company): JobInterface;

    /**
     * Get worker class name
     *
     * @return string
     */
    public function getWorkerClass(): string;

    /**
     * Set worker class name
     *
     * @param string $workerClass
     * @return $this
     */
    public function setWorkerClass(string $workerClass): JobInterface;

    /**
     * Is job enabled
     *
     * @return bool
     */
    public function getEnabled(): bool;

    /**
     * Set job enabled status
     *
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): JobInterface;

    /**
     * Get request index (JSON)
     *
     * @return string|null
     */
    public function getRequestIndex(): ?string;

    /**
     * Set request index (JSON)
     *
     * @param string|null $requestIndex
     * @return $this
     */
    public function setRequestIndex(?string $requestIndex): JobInterface;

    /**
     * Get requests (JSON)
     *
     * @return string|null
     */
    public function getRequests(): ?string;

    /**
     * Set requests (JSON)
     *
     * @param string|null $requests
     * @return $this
     */
    public function setRequests(?string $requests): JobInterface;

    /**
     * Get requests provided when job added flag
     *
     * @return bool
     */
    public function getRequestsProvidedWhenJobAdded(): bool;

    /**
     * Set requests provided when job added flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setRequestsProvidedWhenJobAdded(bool $flag): JobInterface;

    /**
     * Get job data (JSON)
     *
     * @return string|null
     */
    public function getData(): ?string;

    /**
     * Set job data (JSON)
     *
     * @param string|null $data
     * @return $this
     */
    public function setData(?string $data): JobInterface;

    /**
     * Get creation time
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Get update time
     *
     * @return string
     */
    public function getUpdatedAt(): string;
}
