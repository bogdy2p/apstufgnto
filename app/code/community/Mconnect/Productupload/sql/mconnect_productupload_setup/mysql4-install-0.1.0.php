<?php
  /**
 * M-Connect Solutions.
 *
 * NOTICE OF LICENSE
 *

 * It is also available through the world-wide-web at this URL:
 * http://www.mconnectsolutions.com/lab/
 *
 * @category   M-Connect
 * @package    M-Connect
 * @copyright  Copyright (c) 2009-2010 M-Connect Solutions. (http://www.mconnectsolutions.com)
 */ 
?>
<?php
$installer=$this;
$installer->startSetup();
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('mconnectuploadfile')};
CREATE TABLE {$this->getTable('mconnectuploadfile')}
(
`mconnectuploadfile_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`productid` int(11) unsigned NOT NULL,
 `filename` varchar(255) NOT NULL
)ENGINE = MYISAM;");
$installer->run("INSERT INTO {$this->getTable('core_config_data')} (`scope`,`scope_id`,`path`,`value`) values ('default','0','productupload/general/enabled','1');");
$installer->run("INSERT INTO {$this->getTable('core_config_data')} (`scope`,`scope_id`,`path`,`value`) values ('default','0','productupload/general/fileextensions','jpg,png,gif,doc,docx,pdf,rar,zip, txt,xls,xlsx,csv');");
$installer->endSetup();?>