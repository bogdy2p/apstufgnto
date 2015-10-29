<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('productlog')};
CREATE TABLE {$this->getTable('productlog')} (
  `productlog_id` int(11) unsigned NOT NULL auto_increment,
  `product_id` int(11) NOT NULL default 0,
  `type` int(11) NOT NULL default 0,
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`productlog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 