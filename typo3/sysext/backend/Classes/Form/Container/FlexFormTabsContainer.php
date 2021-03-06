<?php
namespace TYPO3\CMS\Backend\Form\Container;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Handle flex forms that have tabs (multiple "sheets").
 *
 * This container is called by FlexFormEntryContainer. It resolves each
 * sheet and hands rendering of single sheet content over to FlexFormElementContainer.
 */
class FlexFormTabsContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();
		$docTemplate = $this->getDocumentTemplate();

		$table = $this->data['tableName'];
		$row = $this->data['databaseRow'];
		$fieldName = $this->data['fieldName']; // field name of the flex form field in DB
		$parameterArray = $this->data['parameterArray'];
		$flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
		$flexFormRowData = $this->data['flexFormRowData'];

		$tabId = 'TCEFORMS:flexform:' . $this->data['parameterArray']['itemFormElName'] . 'lDEF';
		$tabIdString = $docTemplate->getDynTabMenuId($tabId);
		$tabCounter = 0;

		$resultArray = $this->initializeResultArray();
		$tabsContent = array();
		foreach ($flexFormDataStructureArray['sheets'] as $sheetName => $sheetDataStructure) {
			$flexFormRowSheetDataSubPart = $flexFormRowData['data'][$sheetName]['lDEF'] ?: [];

			if (!is_array($sheetDataStructure['ROOT']['el'])) {
				$resultArray['html'] .= LF . 'No Data Structure ERROR: No [\'ROOT\'][\'el\'] found for sheet "' . $sheetName . '".';
				continue;
			}

			$tabCounter ++;

			// Assemble key for loading the correct CSH file
			// @todo: what is that good for? That is for the title of single elements ... see FlexFormElementContainer!
			$dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['ds_pointerField'], TRUE);
			$parameterArray['_cshKey'] = $table . '.' . $fieldName;
			foreach ($dsPointerFields as $key) {
				if ((string)$row[$key] !== '') {
					$parameterArray['_cshKey'] .= '.' . $row[$key];
				}
			}

			$options = $this->data;
			$options['flexFormDataStructureArray'] = $sheetDataStructure['ROOT']['el'];
			$options['flexFormRowData'] = $flexFormRowSheetDataSubPart;
			$options['flexFormFormPrefix'] = '[data][' . $sheetName . '][lDEF]';
			$options['parameterArray'] = $parameterArray;
			// Merge elements of this tab into a single list again and hand over to
			// palette and single field container to render this group
			$options['tabAndInlineStack'][] = array(
				'tab',
				$tabIdString . '-' . $tabCounter,
			);
			$options['renderType'] = 'flexFormElementContainer';
			$childReturn = $this->nodeFactory->create($options)->render();

			$tabsContent[] = array(
				'label' => !empty($sheetDataStructure['ROOT']['sheetTitle']) ? $languageService->sL($sheetDataStructure['ROOT']['sheetTitle']) : $sheetName,
				'content' => $childReturn['html'],
				'description' => $sheetDataStructure['ROOT']['sheetDescription'] ? $languageService->sL($sheetDataStructure['ROOT']['sheetDescription']) : '',
				'linkTitle' => $sheetDataStructure['ROOT']['sheetShortDescr'] ? $languageService->sL($sheetDataStructure['ROOT']['sheetShortDescr']) : '',
			);

			$childReturn['html'] = '';
			$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childReturn);
		}

		// Feed everything to document template for tab rendering
		$resultArray['html'] = $docTemplate->getDynamicTabMenu($tabsContent, $tabId, 1, FALSE, FALSE);
		return $resultArray;
	}

	/**
	 * @throws \RuntimeException
	 * @return DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		$docTemplate = $GLOBALS['TBE_TEMPLATE'];
		if (!is_object($docTemplate)) {
			throw new \RuntimeException('No instance of DocumentTemplate found', 1427143328);
		}
		return $docTemplate;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
