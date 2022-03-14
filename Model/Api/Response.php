<?php

namespace Payoneer\OpenPaymentGateway\Model\Api;

/**
 * Class Response - Validate API response
 */
class Response
{
    /**
     * @var array<mixed>
     */
    protected $responseCodes = [
        'success' => ['200','201','204'],
        'invalid_token' => ['401', '403']
    ];

    /**
     * @param string $key
     * @return array<mixed>
     */
    public function getResponseCode(string $key): array
    {
        return $this->responseCodes[$key];
    }

    /**
     * @param mixed $response
     * @return bool|string
     */
    public function validateResponse($response)
    {
        return in_array($response->getStatus(), $this->getResponseCode('success'));
    }
}
