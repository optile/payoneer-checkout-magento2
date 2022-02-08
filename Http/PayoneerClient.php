<?php

namespace Payoneer\OpenPaymentGateway\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Class Client to manage API client communication
 */
class PayoneerClient
{

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var array <mixed>
     */
    protected $handlers = [];

    /**
     * Client constructor.
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Do API request with provided params
     *
     * @param $hostname
     * @param $endpoint
     * @param array<mixed> $options
     * @param string $requestMethod *
     * @return mixed
     */
    private function doRequest(
        $hostname,
        $endpoint,
        array $options = [],
        $requestMethod = Request::HTTP_METHOD_GET
    ) {
        try {
            $uriEndpoint = $hostname . $endpoint;
            $client = $this->clientFactory->create(['config' => [
                'base_uri' => $hostname
            ]]);
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $options
            );
            $responseBody = $response->getBody();
            $responseBody->rewind();
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }
        return $response;
    }

    /**
     * @param string $method
     * @param $hostname
     * @param $endpoint
     * @param array<mixed> $options
     * @return mixed
     */
    public function send(
        $method,
        $hostname,
        $endpoint,
        array $options = []
    ) {
        $response = $this->doRequest($hostname, $endpoint, $options, $method);
        $responseBody = $response->getBody();
        $responseBody->rewind();
        $responseObject = new \Magento\Framework\DataObject();
        $responseObject->setData('status', $response->getStatusCode());
        $responseObject->setData('reason', $response->getReasonPhrase());
        $responseObject->setData('response', json_decode($responseBody->getContents(), true));
        return $responseObject;
    }
}
