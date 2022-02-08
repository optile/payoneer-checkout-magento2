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
     * @param string $method
     * @param string $hostname
     * @param string $endPoint
     * @param string $merchantCode
     * @param string $apiKey
     * @param array<mixed> $data
     * @return DataObject
     */
    public function send(
        $method,
        $hostname,
        $endPoint,
        $merchantCode,
        $apiKey,
        array $data = []
    ): DataObject {
        $options = [];
        if ($method == WebRequest::HTTP_METHOD_GET) {
            $options['query']   =   $data;
        } else {
            $options['json']  =   $data;
        }
        $options['headers'] = $this->prepareHeaders($merchantCode, $apiKey);

        return $this->payoneerHttpClient->send($method, $hostname, $endPoint, $options);
    }

    /**
     * @param null $merchantCode
     * @param null $appKey
     * @return array
     */
    public function prepareHeaders(
        $merchantCode = null,
        $appKey = null
    ): array {
        $headers = [];
        $headers['Content-Type'] = 'application/vnd.optile.payment.enterprise-v1-extensible+json';
        $headers['Accept'] = 'application/vnd.optile.payment.enterprise-v1-extensible+json';
        $headers['Authorization'] = 'Basic ' . base64_encode($merchantCode . ':' . $appKey);
        return $headers;
    }
}
