<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;

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
     * Cancel constructor.
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}
