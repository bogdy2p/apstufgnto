<?php

$installer = $this;
$installer->startSetup();

$installer->run("UPDATE `eav_attribute`
			SET `frontend_label` = ''
			WHERE `attribute_code` IN ('entry_artist_from_year','entry_artist_to_year','entry_author_from_year','entry_author_to_year','entry_engraver_from_year','entry_engraver_to_year','entry_mapmaker_from_year','entry_mapmaker_to_year')");

$installer->run("UPDATE `eav_attribute`
			SET `frontend_class` = ''
			WHERE `attribute_code` IN ('entry_artist_from_year','entry_artist_to_year','entry_author_from_year','entry_author_to_year','entry_engraver_from_year','entry_engraver_to_year','entry_mapmaker_from_year','entry_mapmaker_to_year')");


$installer->endSetup();