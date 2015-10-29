<?php

class Reea_Entries_Model_Rewrite_Catalog_Resource_Category_Indexer_Product extends Mage_Catalog_Model_Resource_Category_Indexer_Product {
	
	/**
     * Rebuild all index data
     *
     * @return Mage_Catalog_Model_Resource_Category_Indexer_Product
     */
    public function reindexAll()
    {
    	
        $this->useIdxTable(true);
        $this->beginTransaction();
        try {
            $this->clearTemporaryIndexTable();
            $idxTable = $this->getIdxTable();
            $idxAdapter = $this->_getIndexAdapter();
            $stores = $this->_getStoresInfo();
            /**
             * Build index for each store
             */
            foreach ($stores as $storeData) {
                $storeId    = $storeData['store_id'];
                $websiteId  = $storeData['website_id'];
                $rootPath   = $storeData['root_path'];
                $rootId     = $storeData['root_id'];
                /**
                 * Prepare visibility for all enabled store products
                 */
                $enabledTable = $this->_prepareEnabledProductsVisibility($websiteId, $storeId);
                /**
                 * Select information about anchor categories
                 */
                $anchorTable = $this->_prepareAnchorCategories($storeId, $rootPath);
                /**
                 * Add relations between not anchor categories and products
                 */
                $select = $idxAdapter->select();
                /** @var $select Varien_Db_Select */
                $select->from(
                    array('cp' => $this->_categoryProductTable),
                    array('category_id', 'product_id', 'position', 'is_parent' => new Zend_Db_Expr('1'),
                        'store_id' => new Zend_Db_Expr($storeId), 'subject', 'subsubject')
                )
                ->joinInner(array('pv' => $enabledTable), 'pv.product_id=cp.product_id', array('visibility'))
                ->joinLeft(array('ac' => $anchorTable), 'ac.category_id=cp.category_id', array())
                ->where('ac.category_id IS NULL');

                $query = $select->insertFromSelect(
                    $idxTable,
                    array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'subject', 'subsubject', 'visibility'),
                    false
                );
                $idxAdapter->query($query);

                /**
                 * Assign products not associated to any category to root category in index
                 */

                $select = $idxAdapter->select();
                $select->from(
                    array('pv' => $enabledTable),
                    array(new Zend_Db_Expr($rootId), 'product_id', new Zend_Db_Expr('0'), new Zend_Db_Expr('1'),
                        new Zend_Db_Expr($storeId), 'visibility')
                )
                ->joinLeft(array('cp' => $this->_categoryProductTable), 'pv.product_id=cp.product_id', array('subject', 'subsubject'))
                ->where('cp.product_id IS NULL');

                $query = $select->insertFromSelect(
                    $idxTable,
                    array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'visibility', 'subject', 'subsubject'),
                    false
                );
                $idxAdapter->query($query);

                /**
                 * Prepare anchor categories products
                 */
                $anchorProductsTable = $this->_getAnchorCategoriesProductsTemporaryTable();
                $idxAdapter->delete($anchorProductsTable);

                $position = 'MIN('.
                    $idxAdapter->getCheckSql(
                        'ca.category_id = ce.entity_id',
                        $idxAdapter->quoteIdentifier('cp.position'),
                        '('.$idxAdapter->quoteIdentifier('ce.position').' + 1) * '
                        .'('.$idxAdapter->quoteIdentifier('ce.level').' + 1 * 10000)'
                        .' + '.$idxAdapter->quoteIdentifier('cp.position')
                    )
                .')';


                $select = $idxAdapter->select()
                ->useStraightJoin(true)
                ->distinct(true)
                ->from(array('ca' => $anchorTable), array('category_id'))
                ->joinInner(
                    array('ce' => $this->_categoryTable),
                    $idxAdapter->quoteIdentifier('ce.path') . ' LIKE ' .
                    $idxAdapter->quoteIdentifier('ca.path') . ' OR ce.entity_id = ca.category_id',
                    array()
                )
                ->joinInner(
                    array('cp' => $this->_categoryProductTable),
                    'cp.category_id = ce.entity_id',
                    array('product_id')
                )
                ->joinInner(
                    array('pv' => $enabledTable),
                    'pv.product_id = cp.product_id',
                    array('position' => $position)
                )
                ->group(array('ca.category_id', 'cp.product_id'));
                $query = $select->insertFromSelect($anchorProductsTable,
                    array('category_id', 'product_id', 'position'), false);
                $idxAdapter->query($query);

                /**
                 * Add anchor categories products to index
                 */
                $select = $idxAdapter->select()
                ->from(
                    array('ap' => $anchorProductsTable),
                    array('category_id', 'product_id',
                        'position', // => new Zend_Db_Expr('MIN('. $idxAdapter->quoteIdentifier('ap.position').')'),
                        'is_parent' => $idxAdapter->getCheckSql('cp.product_id > 0', 1, 0),
                        'store_id' => new Zend_Db_Expr($storeId))
                )
                ->joinLeft(
                    array('cp' => $this->_categoryProductTable),
                    'cp.category_id=ap.category_id AND cp.product_id=ap.product_id',
                    array('cp.subject', 'cp.subsubject')
                )
                ->joinInner(array('pv' => $enabledTable), 'pv.product_id = ap.product_id', array('visibility'));

                $query = $select->insertFromSelect(
                    $idxTable,
                    array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'subject', 'subsubject', 'visibility'),
                    false
                );
                $idxAdapter->query($query);

                $select = $idxAdapter->select()
                    ->from(array('e' => $this->getTable('catalog/product')), null)
                    ->columns(array(
                        'category_id'   => new Zend_Db_Expr($rootId),
                        'product_id'    => 'e.entity_id',
                        'position'      => new Zend_Db_Expr('0'),
                        'is_parent'     => new Zend_Db_Expr('1'),
                        'store_id'      => new Zend_Db_Expr($storeId),
                        'visibility'    => 'ei.visibility'                        
                    ))
                    ->join(
                        array('ei' => $enabledTable),
                        'ei.product_id = e.entity_id',
                        array())
                    ->joinLeft(
                        array('i' => $idxTable),
                        'i.product_id = e.entity_id AND i.category_id = :category_id AND i.store_id = :store_id',
                        array('subject', 'subsubject'))
                    ->where('i.product_id IS NULL');

                $query = $select->insertFromSelect(
                    $idxTable,
                    array('category_id', 'product_id', 'position', 'is_parent', 'store_id', 'visibility', 'subject', 'subsubject'),
                    false
                );

                $idxAdapter->query($query, array('store_id' => $storeId, 'category_id' => $rootId));
            }

            $this->syncData();

            /**
             * Clean up temporary tables
             */
            $this->clearTemporaryIndexTable();
            $idxAdapter->delete($enabledTable);
            $idxAdapter->delete($anchorTable);
            $idxAdapter->delete($anchorProductsTable);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
        return $this;
    }
    
    /**
     * Reindex not anchor root categories
     *
     * @param array $categoryIds
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Indexer_Product
     */
    protected function _refreshNotAnchorRootCategories(array $categoryIds = null)
    {		
        if (empty($categoryIds)) {
            return $this;
        }

        $adapter = $this->_getWriteAdapter();

        // remove anchor relations
        $where = array(
            'category_id IN(?)' => $categoryIds,
            'is_parent=?'       => 0
        );
        $adapter->delete($this->getMainTable(), $where);

        $stores = $this->_getStoresInfo();
        /**
         * Build index for each store
         */
        foreach ($stores as $storeData) {
            $storeId    = $storeData['store_id'];
            $websiteId  = $storeData['website_id'];
            $rootPath   = $storeData['root_path'];
            $rootId     = $storeData['root_id'];
            if (!in_array($rootId, $categoryIds)) {
                continue;
            }

            $select = $adapter->select()
                ->distinct(true)
                ->from(array('cc' => $this->getTable('catalog/category')), null)
                ->join(
                    array('i' => $this->getMainTable()),
                    'i.category_id = cc.entity_id and i.store_id = 1',
                    array())
                ->joinLeft(
                    array('ie' => $this->getMainTable()),
                    'ie.category_id = ' . (int)$rootId
                        . ' AND ie.product_id=i.product_id AND ie.store_id = ' . (int)$storeId,
                    array())
                ->where('cc.path LIKE ?', $rootPath . '/%')
                ->where('ie.category_id IS NULL')
                ->columns(array(
                    'category_id'   => new Zend_Db_Expr($rootId),
                    'product_id'    => 'i.product_id',
                    'position'      => new Zend_Db_Expr('0'),
                    'is_parent'     => new Zend_Db_Expr('0'),
                    'store_id'      => new Zend_Db_Expr($storeId),
                    'visibility'    => 'i.visibility'
                ));
            $query = $select->insertFromSelect($this->getMainTable());
            $adapter->query($query);

            $visibilityInfo = $this->_getVisibilityAttributeInfo();
            $statusInfo     = $this->_getStatusAttributeInfo();

            $select = $this->_getReadAdapter()->select()
                ->from(array('pw' => $this->_productWebsiteTable), array())
                ->joinLeft(
                    array('i' => $this->getMainTable()),
                    'i.product_id = pw.product_id AND i.category_id = ' . (int)$rootId
                        . ' AND i.store_id = ' . (int) $storeId,
                    array())
                ->join(
                    array('dv' => $visibilityInfo['table']),
                    "dv.entity_id = pw.product_id AND dv.attribute_id = {$visibilityInfo['id']} AND dv.store_id = 0",
                    array())
                ->joinLeft(
                    array('sv' => $visibilityInfo['table']),
                    "sv.entity_id = pw.product_id AND sv.attribute_id = {$visibilityInfo['id']}"
                        . " AND sv.store_id = " . (int)$storeId,
                    array())
                ->join(
                    array('ds' => $statusInfo['table']),
                    "ds.entity_id = pw.product_id AND ds.attribute_id = {$statusInfo['id']} AND ds.store_id = 0",
                    array())
                ->joinLeft(
                    array('ss' => $statusInfo['table']),
                    "ss.entity_id = pw.product_id AND ss.attribute_id = {$statusInfo['id']}"
                        . " AND ss.store_id = " . (int)$storeId,
                    array())
                ->where('i.product_id IS NULL')
                ->where('pw.website_id=?', $websiteId)
                ->where(
                    $this->_getWriteAdapter()->getCheckSql('ss.value_id IS NOT NULL', 'ss.value', 'ds.value') . ' = ?',
                    Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->columns(array(
                    'category_id'   => new Zend_Db_Expr($rootId),
                    'product_id'    => 'pw.product_id',
                    'position'      => new Zend_Db_Expr('0'),
                    'is_parent'     => new Zend_Db_Expr('1'),
                    'store_id'      => new Zend_Db_Expr($storeId),
                    'visibility'    => $adapter->getCheckSql('sv.value_id IS NOT NULL', 'sv.value', 'dv.value')
                ));

            $query = $select->insertFromSelect($this->getMainTable());
            $this->_getWriteAdapter()->query($query);
        }

        return $this;
    }
    
    /**
     * Rebuild index for direct associations categories and products
     *
     * @param null|array $categoryIds
     * @param null|array $productIds
     * @return Mage_Catalog_Model_Resource_Category_Indexer_Product
     */
    protected function _refreshDirectRelations($categoryIds = null, $productIds = null)
    {
        if (!$categoryIds && !$productIds) {
            return $this;
        }

        $visibilityInfo = $this->_getVisibilityAttributeInfo();
        $statusInfo     = $this->_getStatusAttributeInfo();
        $adapter = $this->_getWriteAdapter();
        /**
         * Insert direct relations
         * product_ids (enabled filter) X category_ids X store_ids
         * Validate store root category
         */
        $isParent = new Zend_Db_Expr('1');
        $select = $adapter->select()
            ->from(array('cp' => $this->_categoryProductTable),
                array('category_id', 'product_id', 'position', $isParent, 'subject', 'subsubject'))
            ->joinInner(array('pw'  => $this->_productWebsiteTable), 'pw.product_id=cp.product_id', array())
            ->joinInner(array('g'   => $this->_groupTable), 'g.website_id=pw.website_id', array())
            ->joinInner(array('s'   => $this->_storeTable), 's.group_id=g.group_id', array('store_id'))
            ->joinInner(array('rc'  => $this->_categoryTable), 'rc.entity_id=g.root_category_id', array())
            ->joinInner(
                array('ce'=>$this->_categoryTable),
                'ce.entity_id=cp.category_id AND ('.
                $adapter->quoteIdentifier('ce.path') . ' LIKE ' .
                $adapter->getConcatSql(array($adapter->quoteIdentifier('rc.path') , $adapter->quote('/%'))) .
                ' OR ce.entity_id=rc.entity_id)',
                array())
            ->joinLeft(
                array('dv'=>$visibilityInfo['table']),
                $adapter->quoteInto(
                    "dv.entity_id=cp.product_id AND dv.attribute_id=? AND dv.store_id=0",
                    $visibilityInfo['id']),
                array()
            )
            ->joinLeft(
                array('sv'=>$visibilityInfo['table']),
                $adapter->quoteInto(
                    "sv.entity_id=cp.product_id AND sv.attribute_id=? AND sv.store_id=s.store_id",
                    $visibilityInfo['id']),
                array('visibility' => $adapter->getCheckSql('sv.value_id IS NOT NULL',
                    $adapter->quoteIdentifier('sv.value'),
                    $adapter->quoteIdentifier('dv.value')
                ))
            )
            ->joinLeft(
                array('ds'=>$statusInfo['table']),
                "ds.entity_id=cp.product_id AND ds.attribute_id={$statusInfo['id']} AND ds.store_id=0",
                array())
            ->joinLeft(
                array('ss'=>$statusInfo['table']),
                "ss.entity_id=cp.product_id AND ss.attribute_id={$statusInfo['id']} AND ss.store_id=s.store_id",
                array())
            ->where(
                $adapter->getCheckSql('ss.value_id IS NOT NULL',
                    $adapter->quoteIdentifier('ss.value'),
                    $adapter->quoteIdentifier('ds.value')
                ) . ' = ?',
                Mage_Catalog_Model_Product_Status::STATUS_ENABLED
            );
        if ($categoryIds) {
            $select->where('cp.category_id IN (?)', $categoryIds);
        }
        if ($productIds) {
            $select->where('cp.product_id IN(?)', $productIds);
        }
        $sql = $select->insertFromSelect(
            $this->getMainTable(),
            array('category_id', 'product_id', 'position', 'is_parent', 'subject', 'subsubject', 'store_id', 'visibility'),
            true
        );
        $adapter->query($sql);
        return $this;
    }

/**
     * Rebuild index for anchor categories and associated to child categories products
     *
     * @param null | array $categoryIds
     * @param null | array $productIds
     * @return Mage_Catalog_Model_Resource_Category_Indexer_Product
     */
    protected function _refreshAnchorRelations($categoryIds = null, $productIds = null)
    {
    	
		//die()
		
        if (!$categoryIds && !$productIds) {
            return $this;
        }

        $anchorInfo     = $this->_getAnchorAttributeInfo();
        $visibilityInfo = $this->_getVisibilityAttributeInfo();
        $statusInfo     = $this->_getStatusAttributeInfo();

        /**
         * Insert anchor categories relations
         */
        $adapter = $this->_getReadAdapter();
        $isParent = $adapter->getCheckSql('MIN(cp.category_id)=ce.entity_id', 1, 0);
        $position = 'MIN('.
            $adapter->getCheckSql(
                'cp.category_id = ce.entity_id',
                'cp.position',
                '(cc.position + 1) * ('.$adapter->quoteIdentifier('cc.level').' + 1) * 10000 + cp.position'
            )
        .')';

        $select = $adapter->select()
            ->distinct(true)
            ->from(array('ce' => $this->_categoryTable), array('entity_id'))
            ->joinInner(
                array('cc' => $this->_categoryTable),
                $adapter->quoteIdentifier('cc.path') .
                ' LIKE ('.$adapter->getConcatSql(array($adapter->quoteIdentifier('ce.path'),$adapter->quote('/%'))).')'
                . ' OR cc.entity_id=ce.entity_id'
                , array()
            )
            ->joinInner(
                array('cp' => $this->_categoryProductTable),
                'cp.category_id=cc.entity_id',
                array('cp.product_id', 'position' => $position, 'is_parent' => $isParent, 'subject', 'subsubject')
            )
            ->joinInner(array('pw' => $this->_productWebsiteTable), 'pw.product_id=cp.product_id', array())
            ->joinInner(array('g'  => $this->_groupTable), 'g.website_id=pw.website_id', array())
            ->joinInner(array('s'  => $this->_storeTable), 's.group_id=g.group_id', array('store_id'))
            ->joinInner(array('rc' => $this->_categoryTable), 'rc.entity_id=g.root_category_id', array())
            ->joinLeft(
                array('dca'=>$anchorInfo['table']),
                "dca.entity_id=ce.entity_id AND dca.attribute_id={$anchorInfo['id']} AND dca.store_id=0",
                array())
            ->joinLeft(
                array('sca'=>$anchorInfo['table']),
                "sca.entity_id=ce.entity_id AND sca.attribute_id={$anchorInfo['id']} AND sca.store_id=s.store_id",
                array())
            ->joinLeft(
                array('dv'=>$visibilityInfo['table']),
                "dv.entity_id=pw.product_id AND dv.attribute_id={$visibilityInfo['id']} AND dv.store_id=0",
                array())
            ->joinLeft(
                array('sv'=>$visibilityInfo['table']),
                "sv.entity_id=pw.product_id AND sv.attribute_id={$visibilityInfo['id']} AND sv.store_id=s.store_id",
                array('visibility' => $adapter->getCheckSql(
                    'MIN(sv.value_id) IS NOT NULL',
                    'MIN(sv.value)', 'MIN(dv.value)'
                ))
            )
            ->joinLeft(
                array('ds'=>$statusInfo['table']),
                "ds.entity_id=pw.product_id AND ds.attribute_id={$statusInfo['id']} AND ds.store_id=0",
                array())
            ->joinLeft(
                array('ss'=>$statusInfo['table']),
                "ss.entity_id=pw.product_id AND ss.attribute_id={$statusInfo['id']} AND ss.store_id=s.store_id",
                array())
            /**
             * Condition for anchor or root category (all products should be assigned to root)
             */
            ->where('('.
                $adapter->quoteIdentifier('ce.path') . ' LIKE ' .
                $adapter->getConcatSql(array($adapter->quoteIdentifier('rc.path'), $adapter->quote('/%'))) . ' AND ' .
                $adapter->getCheckSql('sca.value_id IS NOT NULL',
                    $adapter->quoteIdentifier('sca.value'),
                    $adapter->quoteIdentifier('dca.value')) . '=1) OR ce.entity_id=rc.entity_id'
            )
            ->where(
                $adapter->getCheckSql('ss.value_id IS NOT NULL', 'ss.value', 'ds.value') . '=?',
                Mage_Catalog_Model_Product_Status::STATUS_ENABLED
            )
            ->group(array('ce.entity_id', 'cp.product_id', 's.store_id'));
        if ($categoryIds) {
            $select->where('ce.entity_id IN (?)', $categoryIds);
        }
        if ($productIds) {
            $select->where('pw.product_id IN(?)', $productIds);
        }

        $sql = $select->insertFromSelect($this->getMainTable());
        $this->_getWriteAdapter()->query($sql);
        return $this;
    }
	
}
