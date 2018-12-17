<?php

namespace Wagento\FixSalesSequence\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\EntityPool;
use Magento\SalesSequence\Model\Config;
use Magento\Framework\App\ResourceConnection;

class CreateTables extends Command
{
    /**
     * Store code argument
     */
    const STORE_ID_ARGUMENT = 'store_id';

    /**
     * All option
     */
    const ALL_OPTION = 'all';

    /**
     * Store Manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Builder
     */
    private $sequenceBuilder;

    /**
     * @var EntityPool
     */
    private $entityPool;

    /**
     * @var Config
     */
    private $sequenceConfig;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Builder $sequenceBuilder
     * @param EntityPool $entityPool
     * @param Config $sequenceConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Builder $sequenceBuilder,
        EntityPool $entityPool,
        Config $sequenceConfig,
        ResourceConnection $resourceConnection
    ) {
        $this->storeManager = $storeManager;
        $this->sequenceBuilder = $sequenceBuilder;
        $this->entityPool = $entityPool;
        $this->sequenceConfig = $sequenceConfig;
        $this->resourceConnection = $resourceConnection;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('wagento:fixsalessequence')
            ->setDescription('Create missing sales sequence tables')
            ->setDefinition([
                new InputArgument(
                    self::STORE_ID_ARGUMENT,
                    InputArgument::OPTIONAL,
                    'Store Code'
                ),
                new InputOption(
                    self::ALL_OPTION,
                    '--all',
                    InputOption::VALUE_NONE,
                    'All Stores'
                )
            ]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $input->getArgument(self::STORE_ID_ARGUMENT);
        $all = $input->getOption(self::ALL_OPTION);
        $adapter = $this->resourceConnection->getConnection('write');

        if ($all) {
            $storeList = $this->storeManager->getStores();
        }

        if ($storeId !== null) {
            $singleStore = $this->storeManager->getStore($storeId);
            $storeList = [$singleStore];
        }

        foreach ($storeList as $store) {
            foreach ($this->entityPool->getEntities() as $entityType) {

                $sequenceName = $this->getSequenceName( $entityType, $store->getId());

                if ($adapter->isTableExists($sequenceName)) {
                    $output->writeln('<info>' . $sequenceName . ' exists. Skipping.<info>');
                } else {
                    $output->writeln('<info>' . $sequenceName . ' missing. Creating.<info>');
                    $this->sequenceBuilder->setPrefix($store->getId())
                        ->setSuffix($this->sequenceConfig->get('suffix'))
                        ->setStartValue($this->sequenceConfig->get('startValue'))
                        ->setStoreId($store->getId())
                        ->setStep($this->sequenceConfig->get('step'))
                        ->setWarningValue($this->sequenceConfig->get('warningValue'))
                        ->setMaxValue($this->sequenceConfig->get('maxValue'))
                        ->setEntityType($entityType)
                        ->create();
                }

            }
        }

        return $this;
    }

    /**
     * Returns sequence table name
     *
     * @param $entity_type
     * @param $store_id
     * @return string
     */
    protected function getSequenceName($entity_type, $store_id)
    {
        return $this->resourceConnection->getTableName(
            sprintf(
                'sequence_%s_%s',
                $entity_type,
                $store_id
            )
        );
    }

}