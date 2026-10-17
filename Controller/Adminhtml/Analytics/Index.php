<?php
/**
 * Saurabhg_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabhg\Preorder\Controller\Adminhtml\Analytics;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Saurabhg_Preorder::analytics';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Saurabhg_Preorder::analytics');
        $page->addBreadcrumb(__('Reports'), __('Reports'));
        $page->addBreadcrumb(__('Pre Order Analytics'), __('Pre Order Analytics'));
        $page->getConfig()->getTitle()->prepend(__('Pre Order Analytics'));

        return $page;
    }
}
