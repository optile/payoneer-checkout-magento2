<?php

namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class NotificationEmailSender
{
    const NOTIFICATION_EMAIL_TEMPLATE_ID = "notification_email_template";

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Initialize dependencies.
     *    
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Send email from contact form
     *
     * @param string $replyTo
     * @param array $variables
     * @return void
     */
    public function send($notificationResponse)
    {
        $emailTemplateVars = $this->prepareEmailTemplateVars($notificationResponse);
        $storeId = $this->storeManager->getStore()->getId();
        $fromToAddressData = $this->getSenderDetails($storeId);
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier(self::NOTIFICATION_EMAIL_TEMPLATE_ID)
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $storeId
                    ]
                )
                ->setTemplateVars($emailTemplateVars)
                ->setFromByScope($fromToAddressData)
                ->addTo($fromToAddressData)
                ->getTransport();

            $transport->sendMessage();
        } finally {
            //$this->inlineTranslation->resume();
        }
    }

    private function prepareEmailTemplateVars($notificationResponse)
    {
        $emailTemplateVars = [];
        $emailTemplateVars['resultCode'] = isset($notificationResponse['resultCode'])
            ? $notificationResponse['resultCode'] : '';
        $emailTemplateVars['longId'] = isset($notificationResponse['longId'])
            ? $notificationResponse['longId'] : '';
        $emailTemplateVars['transactionId'] = isset($notificationResponse['transactionId'])
            ? $notificationResponse['transactionId'] : '';
        $emailTemplateVars['interactionCode'] = isset($notificationResponse['interactionCode'])
            ? $notificationResponse['interactionCode'] : '';
        $emailTemplateVars['notificationId'] = isset($notificationResponse['notificationId'])
            ? $notificationResponse['notificationId'] : '';
        $emailTemplateVars['reasonCode'] = isset($notificationResponse['reasonCode'])
            ? $notificationResponse['reasonCode'] : '';
        $emailTemplateVars['statusCode'] = isset($notificationResponse['statusCode'])
            ? $notificationResponse['statusCode'] : '';

        return $emailTemplateVars;
    }

    private function getSenderDetails($storeId)
    {
        $senderData = [];
        $senderData['name'] = $this->scopeConfig->getValue(
            'trans_email/ident_general/name',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $senderData['email'] = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $senderData;
    }
}
