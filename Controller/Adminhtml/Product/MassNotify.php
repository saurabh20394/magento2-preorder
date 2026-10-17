<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Saurabhg\Preorder\Model\ReleaseNotification\Sender;

/**
 * Catalog > Products > Actions > Send Pre-Order Release Notification
 */
class MassNotify extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Saurabhg_Preorder::release_notification';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Sender $sender
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly Sender $sender
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $ids = array_map('intval', $this->filter->getCollection($this->collectionFactory->create())->getAllIds());
            $result = $this->sender->send($ids);

            $this->messageManager->addSuccessMessage(
                __('%1 pre-order release notification(s) have been sent.', $result['sent'])
            );
            if ($result['skipped_products']) {
                $this->messageManager->addNoticeMessage(
                    __(
                        '%1 product(s) were skipped because their Backorders value is still "Allow Pre-Orders".',
                        $result['skipped_products']
                    )
                );
            }
            if ($result['failed']) {
                $this->messageManager->addErrorMessage(
                    __('%1 notification(s) could not be sent. See the system log for details.', $result['failed'])
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('catalog/product/index');
    }
}
