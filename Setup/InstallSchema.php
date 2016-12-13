<?php
/**
 * Schema install
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 *
 * @package Webbhuset\SveaWebpay\Setup
 */
class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $tableName = $installer->getTable('sales_invoice');

        $installer->getConnection()
            ->addColumn(
                $tableName,
                'svea_invoice_id',
                [
                    'type'      => Table::TYPE_TEXT,
                    'length'    => 30,
                    'unsigned'  => true,
                    'nullable'  => true,
                    'primary'   => false,
                    'comment'   => 'Svea Invoice ID',
                ]
            );

        $columnNames = [
            'handling_fee_amount'           => 'Handling fee amount',
            'base_handling_fee_amount'      => 'Base handling fee',
            'handling_fee_tax_amount'       => 'Handling fee tax',
            'base_handling_fee_tax_amount'  => 'Base handling fee tax',
            'handling_fee_incl_tax'         => 'Handling fee incl tax',
            'base_handling_fee_incl_tax'    => 'Base handling fee',
        ];

        $tables = [
            'sales_order',
            'quote',
            'quote_address'
        ];

        foreach ($tables as $table) {
            foreach ($columnNames as $columnName => $comment) {
                $installer->getConnection()
                    ->addColumn(
                        $installer->getTable($table),
                        $columnName,
                        [
                            'type'      => Table::TYPE_DECIMAL,
                            'length'    => '12,4',
                            'unsigned'  => true,
                            'nullable'  => true,
                            'default'   => '0.0000',
                            'primary'   => false,
                            'comment'   => $comment,
                        ]
                    );
            }
        }

        $installer->endSetup();
    }
}
