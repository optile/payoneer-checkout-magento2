<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class Cancel
 * Process CANCEL request
 */
class Cancel implements HttpGetActionInterface
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Helper constructor.
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Redirects to cart with message
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $this->messageManager->addErrorMessage(
            __('Something went wrong while processing request. The transaction has been cancelled.')
        );
        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}
