<?php
/**
 * Session Data Interface
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api\Data;

interface SessionInterface
{
    const ENTITY_ID = 'entity_id';
    const TICKET = 'ticket';
    const USER = 'user';
    const COMPANY = 'company';
    const PROGRESS = 'progress';
    const CURRENT_JOB = 'current_job';
    const PENDING_JOBS = 'pending_jobs';
    const ITERATOR_ID = 'iterator_id';
    const ERROR = 'error';
    const STATUS_CODE = 'status_code';
    const STATUS_SEVERITY = 'status_severity';
    const INITIAL_JOB_COUNT = 'initial_job_count';
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
    public function setEntityId(int $entityId): SessionInterface;

    /**
     * Get session ticket
     *
     * @return string
     */
    public function getTicket(): string;

    /**
     * Set session ticket
     *
     * @param string $ticket
     * @return $this
     */
    public function setTicket(string $ticket): SessionInterface;

    /**
     * Get username
     *
     * @return string
     */
    public function getUser(): string;

    /**
     * Set username
     *
     * @param string $user
     * @return $this
     */
    public function setUser(string $user): SessionInterface;

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
    public function setCompany(string $company): SessionInterface;

    /**
     * Get progress percentage
     *
     * @return int
     */
    public function getProgress(): int;

    /**
     * Set progress percentage
     *
     * @param int $progress
     * @return $this
     */
    public function setProgress(int $progress): SessionInterface;

    /**
     * Get current job name
     *
     * @return string|null
     */
    public function getCurrentJob(): ?string;

    /**
     * Set current job name
     *
     * @param string|null $jobName
     * @return $this
     */
    public function setCurrentJob(?string $jobName): SessionInterface;

    /**
     * Get pending jobs (JSON string)
     *
     * @return string|null
     */
    public function getPendingJobs(): ?string;

    /**
     * Set pending jobs (JSON string)
     *
     * @param string|null $jobs
     * @return $this
     */
    public function setPendingJobs(?string $jobs): SessionInterface;

    /**
     * Get iterator ID
     *
     * @return string|null
     */
    public function getIteratorId(): ?string;

    /**
     * Set iterator ID
     *
     * @param string|null $iteratorId
     * @return $this
     */
    public function setIteratorId(?string $iteratorId): SessionInterface;

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getError(): ?string;

    /**
     * Set error message
     *
     * @param string|null $error
     * @return $this
     */
    public function setError(?string $error): SessionInterface;

    /**
     * Get status code
     *
     * @return string|null
     */
    public function getStatusCode(): ?string;

    /**
     * Set status code
     *
     * @param string|null $code
     * @return $this
     */
    public function setStatusCode(?string $code): SessionInterface;

    /**
     * Get status severity
     *
     * @return string|null
     */
    public function getStatusSeverity(): ?string;

    /**
     * Set status severity
     *
     * @param string|null $severity
     * @return $this
     */
    public function setStatusSeverity(?string $severity): SessionInterface;

    /**
     * Get initial job count
     *
     * @return int
     */
    public function getInitialJobCount(): int;

    /**
     * Set initial job count
     *
     * @param int $count
     * @return $this
     */
    public function setInitialJobCount(int $count): SessionInterface;

    /**
     * Get creation time
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): SessionInterface;

    /**
     * Get update time
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set update time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): SessionInterface;
}
