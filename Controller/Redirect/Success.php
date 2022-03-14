<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

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
    protected $context;
    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Success constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param PageFactory $resultPageFactory
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        PageFactory $resultPageFactory,
        Helper $helper
    ) {
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
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
                $payment = $quote->getPayment();

                if (!isset($reqParams['token']) || $payment->getAdditionalInformation('token') != $reqParams['token']) {
                    return $this->helper->redirectToCart(__('Something went wrong while processing payment.'));
                } else {
                    foreach ($this->context->getRequest()->getParams() as $key => $value) {
                        $payment->setAdditionalInformation($key, $value);
                    }
                    $payment->save();
                }

                if (isset($reqParams['customerRegistrationId'])) {
                    $this->helper->saveRegistrationId($reqParams['customerRegistrationId'], $quote->getCustomerId());
                }

                if (!$quote->getCustomerId()) {
                    $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
                }
                $this->cartManagement->placeOrder($cartId);
                return $this->resultPageFactory->create();
            } else {
                return $this->helper->redirectToCart(__('Something went wrong while processing payment.'));
            }
        } catch (\Exception $e) {
            return $this->helper->redirectToCart($e->getMessage());
        }
    }
}
