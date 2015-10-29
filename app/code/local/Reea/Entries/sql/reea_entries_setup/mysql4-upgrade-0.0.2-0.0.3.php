<?php

$installer = $this;

$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');
$idAttributeOldSelect = $this->getAttribute($entityTypeId, 'entry_technique', 'attribute_id');
$installer->updateAttribute($entityTypeId,$idAttributeOldSelect, 
    array('frontend_input'=>'select','source_model'=>'eav/entity_attribute_source_table','backend_type'=>'int')
);

$installer->endSetup();
