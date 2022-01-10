<?php

namespace OJezu\DynamicParameterBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;

class RemoteJsonFileParameterProvider extends JsonParameterProvider
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var CacheInterface|null
     */
    private $cache;

    /**
     * @var string
     */
    private $jsonFileUrl;

    /**
     * @var JsonParameterProvider
     */
    private $alternativeProvider;

    /**
     * @param $jsonFileUrl
     * @param ClientInterface|null $httpClient
     * @param CacheInterface|null $cacheAdapter
     */
    public function __construct(
        $jsonFileUrl,
        ClientInterface $httpClient = null,
        CacheInterface $cacheAdapter = null,
        JsonParameterProvider $alternativeProvider = null
    ) {
        $this->jsonFileUrl = $jsonFileUrl;
        $this->httpClient = $httpClient ? $httpClient : new Client();
        $this->cache = $cacheAdapter;
        $this->alternativeProvider = $alternativeProvider;
    }

    /**
     * @return string
     */
    protected function getFileContents() {
        $cacheKey = 'remoteJsonParameter__'
            . preg_replace('/[^a-zA-Z0-9]+/', '_', $this->jsonFileUrl)
            . '__'
            . substr(sha1($this->jsonFileUrl), 0, 12);
        $content = null;

        if ($this->cache && $this->cache->has($cacheKey)) {
            $content = $this->cache->get($cacheKey);
        } else {
            try {
                $request = $this->httpClient->request('GET', $this->jsonFileUrl, ['http_errors' => true]);
                $content = $request->getBody()->getContents();
            } catch (\Throwable $e) {
                if ($this->alternativeProvider) {
                    $content = $this->alternativeProvider->getFileContents();
                } else {
                    throw $e;
                }
            }
            if ($this->cache) {
                $this->cache->set($cacheKey, $content);
            }
        }

        return $content;
    }
}
