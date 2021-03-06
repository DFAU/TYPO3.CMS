<?php

// Show copied tt_content records in frontend request
$GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = FALSE;

$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] = 'tx_irretutorial_1ncsv_hotels';
$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForMovePlaceholders'] = 'tx_irretutorial_1ncsv_hotels';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'tt_content',
	array(
		'tx_irretutorial_1nff_hotels' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_1nff_hotels',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1nff_hotel',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
					'localizeChildrenAtParentLocalization' => TRUE,
				),
			)
		),
		'tx_irretutorial_1ncsv_hotels' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_1ncsv_hotels',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1ncsv_hotel',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
					'localizeChildrenAtParentLocalization' => TRUE,
				),
				'default' => '',
			)
		),
		'tx_irretutorial_flexform' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_flexform',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => 'FILE:EXT:irre_tutorial/Configuration/FlexForms/tt_content_flexform.xml',
				),
				'default' => '',
			)
		),
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'tt_content',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.div.irre, tx_irretutorial_1nff_hotels, tx_irretutorial_1ncsv_hotels, tx_irretutorial_flexform'
);
