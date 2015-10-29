<?php

class Reea_Entries_Model_Rewrite_Catalog_Resource_Product extends Mage_Catalog_Model_Resource_Product {
	
	/**
     * Save product category relations
     *
     * @param Varien_Object $object
     * @return Mage_Catalog_Model_Resource_Product
     */
    protected function _saveCategories(Varien_Object $object)
    {
		
		//pr($object->getData('category_subjects'));die();
		$l_aCategorySubjects = $object->getData('category_subjects');
		if (!is_array($l_aCategorySubjects)) {
			$l_aCategorySubjects = array();
		}
		
        /**
         * If category ids data is not declared we haven't do manipulations
         */
        if (!$object->hasCategoryIds()) {
            return $this;
        }
        $categoryIds = $object->getCategoryIds();
        $oldCategoryIds = $this->getCategoryIds($object);

        $object->setIsChangedCategories(false);

        $insert = array_diff($categoryIds, $oldCategoryIds);
        $delete = array_diff($oldCategoryIds, $categoryIds);
        
        $need_to_be_updated = array_diff($oldCategoryIds, $delete);
        
        $write = $this->_getWriteAdapter();
        
        if (!empty($need_to_be_updated)) {
            foreach ($need_to_be_updated as $categoryId) {
                if (empty($categoryId)) {
                    continue;
                }
                
                if (isset($l_aCategorySubjects[$categoryId])) {
                
					$data = array(
						'subject'			=> isset($l_aCategorySubjects[$categoryId])?$l_aCategorySubjects[$categoryId]['subject']:null,
						'subsubject'		=> isset($l_aCategorySubjects[$categoryId])?$l_aCategorySubjects[$categoryId]['subsubject']:null
					);
					$write->update($this->_productCategoryTable, $data, 'category_id = '. (int)$categoryId. ' AND product_id = '. (int)$object->getId());
				
				}
                

            }
        }
        
        if (!empty($insert)) {
            $data = array();
            foreach ($insert as $categoryId) {
                if (empty($categoryId)) {
                    continue;
                }
                $data[] = array(
                    'category_id' => (int)$categoryId,
                    'product_id'  => (int)$object->getId(),
                    'position'    => 1,
                    'subject'			=> isset($l_aCategorySubjects[$categoryId])?$l_aCategorySubjects[$categoryId]['subject']:null,
                    'subsubject'		=> isset($l_aCategorySubjects[$categoryId])?$l_aCategorySubjects[$categoryId]['subsubject']:null
                );
            }
            if ($data) {
                $write->insertMultiple($this->_productCategoryTable, $data);
            }
        }

        if (!empty($delete)) {
            foreach ($delete as $categoryId) {
                $where = array(
                    'product_id = ?'  => (int)$object->getId(),
                    'category_id = ?' => (int)$categoryId,
                );

                $write->delete($this->_productCategoryTable, $where);
            }
        }

        if (!empty($insert) || !empty($delete)) {
            $object->setAffectedCategoryIds(array_merge($insert, $delete));
            $object->setIsChangedCategories(true);
        }

        return $this;
    }
	
}
