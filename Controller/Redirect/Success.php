<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
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
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    protected $checkoutSession;

    /**
     * Success constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $cartId = $this->context->getRequest()->getParam('cart_id');
        $listUrl = $this->context->getRequest()->getParam('listUrl', null);
        $interactionCode = $this->context->getRequest()->getParam('interactionCode', null);

        try {
            if ($listUrl && $interactionCode == self::INTERACTION_CODE_PROCEED) {

                /** @var Quote $quote */
                $quote = $this->cartRepository->getActive($cartId);

                if (!$quote->getCustomerId()) {
                    $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
                }

                $this->cartManagement->placeOrder($cartId);

                //$this->checkoutSession->clearStorage();

                return $this->context->getResultRedirectFactory()->create()->setPath('checkout/onepage/success');
            } else {
                return $this->redirectToCart(__('Something went wrong while processing payment.'));
            }
        } catch (\Exception $e) {
            return $this->redirectToCart($e->getMessage());
        }
    }

    /**
     * Clears checkout session
     */
    public function clearCheckoutSession()
    {
        //$this->checkoutSession->clearQuote();
        $this->checkoutSession->clearStorage();
        //$this->checkoutSession->restoreQuote();
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
