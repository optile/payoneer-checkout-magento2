<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class ResponseValidator
 * Payoneer Response Validator
 */
class ResponseValidator extends AbstractValidator
{
    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        $isValid = true;
        $errorMessages = [];

        /*foreach ($this->getResponseValidators() as $validator) {
            $validationResult = $validator($response);

            if (!$validationResult[0]) {
                $isValid = $validationResult[0];
                $errorMessages[] = $validationResult[1];
            }
        }*/

        /*$validationResult = $this->validateResponseCode($response);

        if (!$validationResult) {
            $errorMessages = [__('Transaction has been declined, please, try again later.')];
        }*/

        return $this->createResult($isValid, $errorMessages);
    }
}
