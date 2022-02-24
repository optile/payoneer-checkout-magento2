<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Command;

use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class HostedListCommand
 * Class for Payoneer hosted payment command
 */
class AuthorizeCommand extends GatewayCommand
{
    protected $requestBuilder; // @codingStandardsIgnoreLine
    protected $transferFactory; // @codingStandardsIgnoreLine
    protected $client; // @codingStandardsIgnoreLine
    protected $logger; // @codingStandardsIgnoreLine
    protected $handler; // @codingStandardsIgnoreLine
    protected $validator; // @codingStandardsIgnoreLine

    public function __construct(
        BuilderInterface $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface $client,
        LoggerInterface $logger,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null
    ) {

        parent::__construct($requestBuilder, $transferFactory, $client, $logger, $handler, $validator);
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->logger = $logger;
        $this->handler = $handler;
        $this->validator = $validator;
    }

    /**
     * @param array $commandSubject
     * @return array|void
     * @throws ClientException
     * @throws ConverterException
     */
    public function execute(array $commandSubject)
    {
        $transferO = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        return $this->client->placeRequest($transferO);
    }
}
