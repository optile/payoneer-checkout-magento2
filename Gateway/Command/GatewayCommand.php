<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Command;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\ResultInterface as CommandResultInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Payoneer\OpenPaymentGateway\Gateway\Http\TransferFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GatewayCommand
 *
 * GatewayCommand for Payoneer payment
 */
class GatewayCommand implements CommandInterface
{
    /**
     * @var BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var TransferFactoryInterface
     */
    protected $transferFactory;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var HandlerInterface|null
     */
    protected $handler;

    /**
     * @var ValidatorInterface|null
     */
    protected $validator;

    /**
     * @var ErrorMessageMapperInterface|null
     */
    private $errorMessageMapper;

    /**
     * @var Session
     */
    private $session;

    /**
     * Gateway command constructor
     *
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param HandlerInterface $handler
     * @param ValidatorInterface $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     *
     */
    public function __construct(
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        LoggerInterface $logger,
        Session $checkoutSession,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->logger = $logger;
        $this->session = $checkoutSession;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->errorMessageMapper = $errorMessageMapper;
    }

    /**
     * Executes command basing on business object
     *
     * @param array <mixed> $commandSubject
     * @return CommandResultInterface | array <mixed>
     * @throws CommandException
     * @throws ClientException
     * @throws ConverterException
     */
    public function execute(array $commandSubject)
    {
        $response = [];
        $payment = SubjectReader::readPayment($commandSubject);
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject),
            $payment
        );
        //process payoneer request only if it is not via fetch or notification
        if ($this->session->getFetchNotificationResponse()) {
            $response = $this->session->getFetchNotificationResponse();
        } else {
            $response = $this->client->placeRequest($transferO);

            if ($this->validator !== null) {
                $result = $this->validator->validate(
                    array_merge($commandSubject, ['response' => $response])
                );
                if (!$result->isValid()) {
                    $this->processErrors($result);
                }
            }

        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }

        if ($this->session->getFetchNotificationResponse()) {
            $this->session->unsFetchNotificationResponse();
        }

        return $response;
    }

    /**
     * Tries to map error messages from validation result and logs processed message.
     * Throws an exception with mapped message or default error.
     *
     * @param ResultInterface $result
     * @return void
     * @throws CommandException
     */
    private function processErrors(ResultInterface $result)
    {
        $messages = [];
        $errorCodeOrMessage = null;
        $errorsSource = array_merge($result->getErrorCodes(), $result->getFailsDescription());

        foreach ($errorsSource as $errorCodeOrMessage) {
            $errorCodeOrMessage = (string) $errorCodeOrMessage;

            // error messages mapper can be not configured if payment method doesn't have custom error messages.
            if ($this->errorMessageMapper !== null) {
                $mapped = (string) $this->errorMessageMapper->getMessage($errorCodeOrMessage);
                if (!empty($mapped)) {
                    $messages[] = $mapped;
                    $errorCodeOrMessage = $mapped;
                }
            }

            $this->logger->critical('Payment Error: ' . $errorCodeOrMessage);
        }

        $exceptionMessage = $errorCodeOrMessage ?: 'Transaction declined. Try again later.';

        throw new CommandException(
            !empty($messages)
                ? __(implode(PHP_EOL, $messages))
                : __($exceptionMessage)
        );
    }
}
