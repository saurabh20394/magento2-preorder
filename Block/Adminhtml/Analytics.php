<?php
/**
 * Saurabh_Preorder
 *
 * @copyright Copyright (C) 2026 Saurabh Gupta
 * @license   GPL-3.0-or-later
 */
declare(strict_types=1);

namespace Saurabh\Preorder\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Model\Currency;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use Saurabh\Preorder\Model\Config;
use Saurabh\Preorder\Model\ResourceModel\Analytics as AnalyticsResource;

/**
 * Reports > Pre Order Analytics
 */
class Analytics extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Saurabh_Preorder::analytics.phtml';

    private ?array $period = null;

    /**
     * @param Context $context
     * @param AnalyticsResource $analytics
     * @param Config $config
     * @param Currency $currency
     * @param StatusCollectionFactory $statusCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly AnalyticsResource $analytics,
        private readonly Config $config,
        private readonly Currency $currency,
        private readonly StatusCollectionFactory $statusCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Requested period as local dates (Y-m-d), defaults to the last 30 days
     *
     * @return array{from: string, to: string}
     */
    public function getPeriod(): array
    {
        if ($this->period === null) {
            $today = $this->_localeDate->date()->format('Y-m-d');
            $from = $this->validDate((string)$this->getRequest()->getParam('from'))
                ?? $this->_localeDate->date()->modify('-30 days')->format('Y-m-d');
            $to = $this->validDate((string)$this->getRequest()->getParam('to')) ?? $today;
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $this->period = ['from' => $from, 'to' => $to];
        }

        return $this->period;
    }

    public function getSummary(): array
    {
        [$from, $to] = $this->getUtcPeriod();

        return $this->analytics->getSummary(
            $from,
            $to,
            $this->config->getPendingStatuses(),
            $this->config->getRevenueStatuses()
        );
    }

    public function getTopProducts(): array
    {
        return $this->analytics->getTopProducts(...$this->getUtcPeriod());
    }

    public function getTopCustomers(): array
    {
        return $this->analytics->getTopCustomers(...$this->getUtcPeriod());
    }

    /**
     * @return array<string, int> status label => orders count
     */
    public function getStatuses(): array
    {
        $labels = $this->statusCollectionFactory->create()->toOptionHash();
        $result = [];
        foreach ($this->analytics->getStatuses(...$this->getUtcPeriod()) as $status => $count) {
            $result[(string)($labels[$status] ?? $status)] = $count;
        }

        return $result;
    }

    public function formatAmount($amount): string
    {
        $currency = $this->currency->load(
            (string)$this->_scopeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE)
        );

        return $currency->formatTxt((float)$amount);
    }

    public function formatQty($qty): string
    {
        return (string)((float)$qty * 1);
    }

    public function getFilterUrl(): string
    {
        return $this->getUrl('*/*/index');
    }

    public function getConfigUrl(): string
    {
        return $this->getUrl('adminhtml/system_config/edit', ['section' => 'saurabh_preorder']);
    }

    /**
     * @return string[]
     */
    private function getUtcPeriod(): array
    {
        $period = $this->getPeriod();

        return [
            $this->_localeDate->convertConfigTimeToUtc($period['from'] . ' 00:00:00'),
            $this->_localeDate->convertConfigTimeToUtc($period['to'] . ' 23:59:59'),
        ];
    }

    private function validDate(string $value): ?string
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
