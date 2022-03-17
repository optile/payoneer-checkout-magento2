<?php
namespace Payoneer\OpenPaymentGateway\Model\Api;

use Magento\Framework\DataObject;
use Magento\Framework\Webapi\Rest\Request as WebRequest;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Http\PayoneerClient;

/**
 * Class Request - Manage Payoneer API requests
 */
class Request
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PayoneerClient
     */
    protected $payoneerHttpClient;

    /**
     * Request constructor.
     * @param Config $config
     * @param PayoneerClient $payoneerHttpClient
     */
    public function __construct(
        Config $config,
        PayoneerClient $payoneerHttpClient
    ) {
        $this->config = $config;
        $this->payoneerHttpClient = $payoneerHttpClient;
    }

    /**
     * @param int|string $method
     * @param string $endPoint
     * @param array <mixed> $credentials
     * @param array <mixed>|string $data
     * @return DataObject
     */
    public function send(
        $method,
        $endPoint,
        $credentials,
        $data
    ): DataObject {
        $options = [];
        if ($method == WebRequest::HTTP_METHOD_GET) {
            $options['query']   =   $data;
        } else {
            $options['json']  =   $data;
        }
        $options['headers'] = $this->config->prepareHeaders($credentials['merchantCode'], $credentials['apiKey']);

        return $this->payoneerHttpClient->send($method, $credentials['hostName'], $endPoint, $options);
    }
}
