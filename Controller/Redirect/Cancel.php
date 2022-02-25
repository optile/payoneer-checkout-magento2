<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class Cancel
 * Process CANCEL request
 */
class Cancel implements HttpGetActionInterface
{

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Cancel constructor.
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        Helper $helper
    ) {
        $this->context = $context;
        $this->helper = $helper;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        return $this->helper->redirectToCart(__('Something went wrong while processing request CANCEL'));
    }
}
