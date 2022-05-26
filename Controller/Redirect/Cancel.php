<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Payoneer\OpenPaymentGateway\Http\PayoneerClient;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class Cancel
 * Process CANCEL request
 */
class Cancel implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * Helper constructor.
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param PageFactory $resultPageFactory
     * @param CheckoutSession $checkoutSession
     * @param Helper $helper
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        PageFactory $resultPageFactory,
        CheckoutSession $checkoutSession,
        Helper $helper,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->context = $context;
        $this->cartRepository = $cartRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $reqParams = $this->context->getRequest()->getParams();

        try {
            $this->checkoutSession->setPayoneerInvalidTxn(true);

            $this->saveCartAndPlaceOrder($reqParams);

            $this->helper->redirectToReorderCart();

            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        } catch (\Exception $e) {
            return $this->resultPageFactory->create();
        }
    }

    /**
     * @param array <mixed> $reqParams
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @return void
     */
    public function saveCartAndPlaceOrder($reqParams)
    {
        $cartId = $reqParams['cart_id'];
        if ($cartId) {
            /** @var Quote $quote */
            $quote = $this->cartRepository->getActive($reqParams['cart_id']);
            $payment = $quote->getPayment();
            foreach ($reqParams as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
            $quote->setPayment($payment);

            if (!$quote->getCustomerId()) {
                $quote = $this->helper->setGuestCustomerEmail($quote);
            }
            $this->cartRepository->save($quote);

            $this->helper->placeOrder($reqParams['cart_id']);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
