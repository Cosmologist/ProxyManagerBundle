<?php

namespace Cosmologist\ProxyManagerBundle\Service;

use Cosmologist\ProxyManagerBundle\Service\Exceptions\ProxiesEndedException;

/**
 * Proxy manager
 */
class Manager
{
    /**
     * List of proxy
     *
     * @var Proxy[]
     */
    private $proxies = [];

    /**
     * The minimum period between the proxy using
     *
     * @var int
     */
    private $minAccessPeriod;

    /**
     * The number of unsuccessful attempts to ignore proxy
     *
     * @var int
     */
    private $maxFailedAccessCount;

    /**
     * Constructor
     *
     * @param array|\Traversable $proxies              Proxy address list
     * @param int                $minAccessPeriod      The minimum period between the proxy using
     * @param int                $maxFailedAccessCount The number of unsuccessful attempts to ignore proxy
     */
    public function __construct($proxies = [], $minAccessPeriod = 2, $maxFailedAccessCount = 2)
    {
        $this->setProxies($proxies);

        $this->minAccessPeriod = $minAccessPeriod;
        $this->maxFailedAccessCount = $maxFailedAccessCount;
    }

    /**
     * Set proxy address list
     *
     * @param $proxies
     */
    public function setProxies($proxies)
    {
        foreach ($proxies as $proxy) {
            if (!empty($proxy)) {
                $this->proxies[] = new Proxy($proxy);
            }
        }
    }

    /**
     * Find proxy object by address
     *
     * @param string $address Address
     *
     * @return Proxy
     */
    public function findProxyByAddress($address)
    {
        foreach ($this->proxies as $proxy) {
            if ($proxy->getAddress() === $address) {
                return $proxy;
            }
        }

        return null;
    }

    /**
     * Get a suitable and working proxy
     *
     * @return Proxy
     */
    public function getProxy()
    {
        $haveGoodProxy = true;
        while ($haveGoodProxy) {
            $haveGoodProxy = false;
            foreach ($this->proxies as $proxy) {
                if ($proxy->getLastAccessTimestamp() > (time() - $this->minAccessPeriod)) {
                    $haveGoodProxy = true;
                    continue;
                }

                if ($proxy->getFailedAttemptsCount() >= $this->maxFailedAccessCount) {
                    continue;
                }

                $proxy->setLastAccessTimestamp(time());

                return $proxy;
            }
        }

        throw new ProxiesEndedException('Proxies was ended');
    }
}
