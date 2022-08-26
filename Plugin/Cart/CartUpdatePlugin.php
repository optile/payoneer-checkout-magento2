<?php

namespace Payoneer\OpenPaymentGateway\Plugin\Cart;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\ListUpdateTransactionService;

/**
 * CartUpdatePlugin - Update list if list session already exists
 */
class CartUpdatePlugin
{
    const LIST_EXPIRED = 'list_expired';

    /**
     * @var ListUpdateTransactionService
     */
    private $transactionService;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * CartUpdatePlugin constructor.
     * @param ListUpdateTransactionService $transactionService
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        ListUpdateTransactionService $transactionService,
        Config $config,
        CartRepositoryInterface $cartRepository
    ) {
        $this->transactionService = $transactionService;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param Cart $cart
     * @param mixed $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSave(Cart $cart, $result)
    {
        if (!$this->config->isPayoneerEnabled()) {
            return $result;
        }

        $quoteId = $cart->getQuote()->getId();
        $quote = $this->cartRepository->get($quoteId);
        $payment = $quote->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $listId = isset($additionalInformation[Config::LIST_ID]) ? $additionalInformation[Config::LIST_ID] : null;
        if (!$listId) {
            return $result;
        } else {
            /** @var array <mixed> $response */
            $response = $this->transactionService->process($quote->getPayment(), Config::LIST_UPDATE);
            if ($this->isListExpired($response)) {
                $payment->setAdditionalInformation(Config::LIST_ID, null);
                $payment->setAdditionalInformation(Config::REDIRECT_URL, null);
                $quote->setPayment($payment);
                $this->cartRepository->save($quote);
            }
            elseif ($this->updateError($response)) {
                $this->transactionService->process($quote->getPayment(), Config::LIST_DELETE);
                $payment->setAdditionalInformation(Config::LIST_ID, null);
                $payment->setAdditionalInformation(Config::REDIRECT_URL, null);
                $quote->setPayment($payment);
                $this->cartRepository->save($quote);
            }
        }
        return $result;
    }

    /**
     * @param array <mixed> $result
     * @return bool
     */
    public function isListExpired($result)
    {
        if (isset($result['reason']) && str_contains($result['reason'], self::LIST_EXPIRED)) {
            return true;
        }
        return false;
    }

    /**
     * @param array <mixed> $result
     * @return bool
     */
    public function updateError($result)
    {
        if(isset($result['status']) && $result['status'] == 422)
        {
            return true;
        }
        else {
            return false;
        }
    }
}
