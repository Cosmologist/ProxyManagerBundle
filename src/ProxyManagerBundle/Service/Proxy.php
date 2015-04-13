<?php

namespace Cosmologist\ProxyManagerBundle\Service;

/**
 * Proxy
 */
class Proxy
{
    /** Proxy address, like host:port
     *
     * @var string
     */

    private $address;

    /**
     * Counter unsuccessful attempts to use
     *
     * @var int
     */
    private $failedAttemptsCount = 0;

    /**
     * Last timestamp when the proxy was used
     *
     * @var int
     */
    private $lastAccessTimestamp;


    /**
     * Constructor
     *
     * @param string $address Proxy address, like host:port
     */
    public function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * Return proxy address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Return count of unsuccessful attempts to use
     *
     * @return int
     */
    public function getFailedAttemptsCount()
    {
        return $this->failedAttemptsCount;
    }


    /**
     * Increase count of unsuccessful attempts to use
     */
    public function increaseFailedAttemptsCount()
    {
        $this->failedAttemptsCount++;
    }

    /**
     * Return last timestamp when the proxy was used
     *
     * @return int
     */
    public function getLastAccessTimestamp()
    {
        return $this->lastAccessTimestamp;
    }

    /**
     * Set last timestamp when the proxy was used
     *
     * @param mixed $lastAccessTimestamp
     */
    public function setLastAccessTimestamp($lastAccessTimestamp)
    {
        $this->lastAccessTimestamp = $lastAccessTimestamp;
    }
}