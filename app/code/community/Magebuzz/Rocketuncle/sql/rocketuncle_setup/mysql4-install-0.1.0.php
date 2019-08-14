<?php
$installer = $this;
$installer->startSetup();
$installer->run("
 ALTER TABLE {$this->getTable('sales_flat_order')} ADD `rocketuncle_status` INT(11) NOT NULL DEFAULT '0';     
 ALTER TABLE {$this->getTable('sales_flat_order')} ADD `rocketuncle_information` TEXT NULL;       
");

$installer->endSetup(); 