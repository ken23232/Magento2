<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */
namespace Buckaroo\Magento2\Controller\Applepay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class UpdateShippingMethods extends Common
{
    public function execute()
    {
        $isPost = $this->getRequest()->getPostValue();
        $errorMessage = false;
        $data = [];

        if ($isPost) {
            if ($wallet = $this->getRequest()->getParam('wallet')) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();//instance of object manager
                $checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
                $quoteRepository = $objectManager->get('Magento\Quote\Model\QuoteRepository');
                $quote = $checkoutSession->getQuote();

                ////shipping
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->getShippingAddress()->setShippingMethod($wallet['identifier']);

                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
                $totals = $this->gatherTotals($quote->getShippingAddress(), $quote->getTotals());
                $quoteRepository->save($quote);
                $data = [
                    'shipping_methods' => [
                        'code' => $wallet['identifier']
                    ],
                    'totals' => $totals
                ];

                //resave proper method
                $quote->getShippingAddress()->setShippingMethod($wallet['identifier']);
                $quote->getShippingAddress()->save();
            }
        }

        if ($errorMessage) {
            $response = ['success' => 'false'];
        } else {
            $response = ['success' => 'true', 'data' => $data];
        }
        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($response);
    }

}