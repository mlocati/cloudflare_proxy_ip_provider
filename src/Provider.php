<?php

namespace CloudflareProxyIPProvider;

use ArrayAccess;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Http\Client\Client as HttpClient;
use Exception;
use ProxyIPManager\Provider\ProviderInterface;
use Throwable;

class Provider implements ProviderInterface
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Concrete\Core\Http\Client\Client
     */
    protected $httpClient;

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Config\Repository\Repository $config
     * @param \Concrete\Core\Http\Client\Client $httpClient
     */
    public function __construct(Repository $config, HttpClient $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ProviderInterface::getName()
     */
    public function getName()
    {
        return t('CloudFlare');
    }

    /**
     * {@inheritdoc}
     *
     * @see \ProxyIPManager\Provider\ProviderInterface::getProxyIPs()
     */
    public function getProxyIPs(ArrayAccess $errors, array $configuration = null)
    {
        $result = [];
        foreach ($this->config->get('cloudflare_proxy_ip_provider::endpoints') as $endPoint) {
            try {
                $result = array_merge($result, $this->getProxyIPsFromEndPoint($endPoint));
            } catch (Exception $x) {
                $errors[] = $x->getMessage();
            } catch (Throwable $x) {
                $errors[] = $x->getMessage();
            }
        }

        return $result;
    }

    /**
     * @param string $endPoint
     *
     * @throws \Exception
     *
     * @return string[]
     */
    protected function getProxyIPsFromEndPoint($endPoint)
    {
        try {
            $this->httpClient->reset()->setUri($endPoint);
        } catch (Exception $x) {
            throw new Exception(t('Failed to set the HTTP Client endpoint url to %1$s: %2$s', $endPoint, $x->getMessage()));
        }
        try {
            $response = $this->httpClient->send();
        } catch (Exception $x) {
            throw new Exception(t('Failed to send the HTTP request to %1$s: %2$s', $endPoint, $x->getMessage()));
        }
        if (!$response->isOk()) {
            throw new Exception(t('Bad response code (%1$s) from the HTTP request to %2$s.', $response->getStatusCode(), $endPoint));
        }

        return preg_split('/\s+/', $response->getBody(), -1, PREG_SPLIT_NO_EMPTY);
    }
}
