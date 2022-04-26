<?php

namespace Payoneer\OpenPaymentGateway\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Class PayoneerClient to manage API client communication
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
     * PayoneerClient constructor.
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
     * @param int|string $method
     * @param string $hostname
     * @param string $endpoint
     * @param array <mixed> $options
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

    /**
     * Do API request with provided params
     *
     * @param string $hostname
     * @param string $endpoint
     * @param array <mixed> $options
     * @param string|int $requestMethod
     * @return mixed
     */
    private function doRequest(
        $hostname,
        $endpoint,
        $options = [],
        $requestMethod = Request::HTTP_METHOD_GET
    ) {
        try {
            $uriEndpoint = $hostname . $endpoint;
            $client = $this->clientFactory->create(['config' => [
                'base_uri' => $hostname
            ]]);

            $response = $client->request(
                (string)$requestMethod,
                $uriEndpoint,
                $options
            );
        } catch (GuzzleException $exception) {
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }
        return $response;
    }
}
