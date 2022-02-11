<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Api\Request;

/**
 * Class Success
 * Process SUCCESS request
 */
class Success implements HttpGetActionInterface
{
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

    /**
     * Success constructor.
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository
    ) {
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        try {
            $cartId = $this->context->getRequest()->getParam('cart_id');
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($cartId);
            /*if ($quote->getPayment()->getAdditionalInformation(Config::ACCESS_CODE) != $accessCode) {
                throw new LocalizedException(__('Your session is expired, please try again.'));
            }*/

            if (!$quote->getCustomerId()) {
                $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
            }
            $this->cartManagement->placeOrder($cartId);

            return $this->context->getResultRedirectFactory()->create()->setPath('checkout/onepage/success');
        } catch (\Exception $e) {
            $this->context->getMessageManager()->addErrorMessage($e->getMessage());
            return $this->context->getResultRedirectFactory()->create()->setPath('checkout/cart');
        }
    }
}
