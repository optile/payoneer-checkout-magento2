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
    private $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Success constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        PageFactory $resultPageFactory
    ) {
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Dispatch request
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $cartId = $this->context->getRequest()->getParam('cart_id');
        $listUrl = $this->context->getRequest()->getParam('listUrl', null);
        $interactionCode = $this->context->getRequest()->getParam('interactionCode', null);
        $token = $this->context->getRequest()->getParam('token', null);

        try {
            if ($listUrl && $interactionCode == self::INTERACTION_CODE_PROCEED) {

                /** @var Quote $quote */
                $quote = $this->cartRepository->getActive($cartId);
                $payment = $quote->getPayment();

                if ($payment->getAdditionalInformation('token') != $token) {
                    return $this->redirectToCart(__('Something went wrong while processing payment22.'));
                } else {
                    foreach ($this->context->getRequest()->getParams() as $key => $value) {
                        $payment->setAdditionalInformation($key, $value);
                    }
                    $payment->save();
                }

                if (!$quote->getCustomerId()) {
                    $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
                }

                $this->cartManagement->placeOrder($cartId);
                return $this->resultPageFactory->create();
            } else {
                return $this->redirectToCart(__('Something went wrong while processing payment.'));
            }
        } catch (\Exception $e) {
            return $this->redirectToCart($e->getMessage());
        }
    }

    /**
     * Redirects to cart
     *
     * @param string $message
     * @return Redirect
     */
    public function redirectToCart($message)
    {
        $this->context->getMessageManager()->addErrorMessage($message);
        return $this->context->getResultRedirectFactory()->create()->setPath('checkout/cart');
    }
}
