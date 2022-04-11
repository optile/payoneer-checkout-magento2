<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Class ResponseValidator
 * Payoneer Response Validator
 */
class ResponseValidator extends AbstractValidator
{
    const REFUND_PAID_OUT_STATUS        = 'paid_out';
    const CAPTURE_STATUS                = 'charged';
    const REFUND_CREDITED               = 'refund_credited';
    const AUTH_CANCEL_PENDING_STATUS    = 'pending';
    const CANCELLATION_REQUESTED        = 'cancelation_requested';

    /**
     * @var bool
     */
    private $skipValidation;

    /**
     * @var mixed
     */
    private $successStatusCode;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param bool $skipValidation
     * @param mixed $successStatusCode
     * @return void
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        $skipValidation = false,
        $successStatusCode = ''
    ) {
        parent::__construct($resultFactory);
        $this->skipValidation = $skipValidation;
        $this->successStatusCode = $successStatusCode;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array <mixed> $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);
        if ($this->skipValidation == true) {
            return $this->createResult(true);
        }
        if ($response['status'] != 200) {
            return $this->createResult(false, [$response['reason']]);
        }

        if (isset($response['response']['status']['code']) &&
            $response['response']['status']['code'] != $this->successStatusCode
        ) {
            return $this->createResult(false, [$response['response']['resultInfo']]);
        }
        return $this ->createResult(true);
    }
}
