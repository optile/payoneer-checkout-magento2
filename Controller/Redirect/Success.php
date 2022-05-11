<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields as AdminFields;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class Success
 * Process SUCCESS request
 */
class Success implements HttpGetActionInterface
{
    const INTERACTION_CODE_PROCEED = 'PROCEED';

    /**
     * @var Context
     */
    private $context;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Config
     */
    private $config;

    /**
     * Success constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param PageFactory $resultPageFactory
     * @param Helper $helper
     * @param Session $checkoutSession
     * @param Config $config
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        PageFactory $resultPageFactory,
        Helper $helper,
        Session $checkoutSession,
        Config $config
    ) {
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * Dispatch request
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $reqParams = $this->context->getRequest()->getParams();
        try {
            if (isset($reqParams['listUrl']) &&
                isset($reqParams['interactionCode']) &&
                $reqParams['interactionCode'] == self::INTERACTION_CODE_PROCEED
            ) {
                $cartId = $reqParams['cart_id'];
                /** @var Quote $quote */
                $quote = $this->cartRepository->getActive($cartId);
                $quoteData = $quote->getData();
                $payment = $quote->getPayment();
                if (!isset($reqParams['token'])
                    || $reqParams['token'] == ''
                    || $reqParams['token'] == null
                    || $payment->getAdditionalInformation('token') != $reqParams['token']
                ) {
                    return $this->redirectToCart();
                } else {
                    foreach ($this->context->getRequest()->getParams() as $key => $value) {
                        $payment->setAdditionalInformation($key, $value);
                    }
                    if ($this->config->getValue('payment_action') == AdminFields::CAPTURE) {
                        $payment->setAdditionalInformation('payoneerCapture', 'Success');
                    }
                    $quote->setPayment($payment);
                }

                if ($quote->getCustomerId() && isset($reqParams['customerRegistrationId'])) {
                    $this->helper->saveRegistrationId($reqParams['customerRegistrationId'], $quote->getCustomerId());
                }

                if (!$quote->getCustomerId()) {
                    $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
                    $customerEmail =  $quote->getCustomerEmail() ?: $quote->getBillingAddress()->getEmail();
                    if (!$customerEmail) {
                        $quote->setCustomerEmail($this->checkoutSession->getPayoneerCustomerEmail());
                    }
                } else {
                    $this->unsetPayoneerCustomerEmailSession();
                }
                $this->cartRepository->save($quote);

                if ($quoteData['grand_total'] != $reqParams['amount']) {
                    $this->checkoutSession->setUpdateOrderStatus(true);
                }

                $this->cartManagement->placeOrder($cartId);

                $this->unsetPayoneerCountryIdSession();
                return $this->resultPageFactory->create();
            } else {
                return $this->redirectToCart();
            }
        } catch (\Exception $e) {
            return $this->redirectToCart($e->getMessage());
        }
    }

    /**
     * @param string|null $message
     * @return Redirect
     */
    public function redirectToCart($message = null)
    {
        if (!$message) {
            $message = 'Something went wrong while processing payment. Invalid response from Payoneer';
        }
        return $this->helper->redirectToCart(__($message));
    }

    /**
     * Unset custom checkout session variable
     * @return void
     */
    public function unsetPayoneerCustomerEmailSession()
    {
        if ($this->checkoutSession->getPayoneerCustomerEmail()) {
            $this->checkoutSession->unsPayoneerCustomerEmail();
        }
    }

    /**
     * Unset custom checkout session variable
     * @return void
     */
    public function unsetPayoneerCountryIdSession()
    {
        if ($this->checkoutSession->getShippingCountryId()) {
            $this->checkoutSession->unsShippingCountryId();
        }
        if ($this->checkoutSession->getBillingCountryId()) {
            $this->checkoutSession->unsBillingCountryId();
        }
    }
}
