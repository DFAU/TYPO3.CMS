<?php
namespace TYPO3\CMS\Backend\Controller\File;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Script Class for the rename-file form.
 */
class RenameFileController {

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @internal
	 */
	public $doc;

	/**
	 * Name of the filemount
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Target path
	 *
	 * @var string
	 * @internal
	 */
	public $target;

	/**
	 * The file or folder object that should be renamed
	 *
	 * @var \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject
	 */
	protected $fileOrFolderObject;

	/**
	 * Return URL of list module.
	 *
	 * @var string
	 */
	public $returnUrl;

	/**
	 * Accumulating content
	 *
	 * @var string
	 * @internal
	 */
	public $content;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['SOBE'] = $this;
		$this->init();
	}

	/**
	 * Initialize
	 *
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException
	 */
	protected function init() {
		// Initialize GPvars:
		$this->target = GeneralUtility::_GP('target');
		$this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
		// Cleaning and checking target
		if ($this->target) {
			$this->fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->target);
		}
		if (!$this->fileOrFolderObject) {
			$title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', TRUE);
			$message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', TRUE);
			throw new \RuntimeException($title . ': ' . $message, 1294586844);
		}
		if ($this->fileOrFolderObject->getStorage()->getUid() === 0) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889840);
		}

		// If a folder should be renamed, AND the returnURL should go to the old directory name, the redirect is forced
		// so the redirect will NOT end in an error message
		// this case only happens if you select the folder itself in the foldertree and then use the clickmenu to
		// rename the folder
		if ($this->fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$parsedUrl = parse_url($this->returnUrl);
			$queryParts = GeneralUtility::explodeUrl2Array(urldecode($parsedUrl['query']));
			if ($queryParts['id'] === $this->fileOrFolderObject->getCombinedIdentifier()) {
				$this->returnUrl = str_replace(urlencode($queryParts['id']), urlencode($this->fileOrFolderObject->getStorage()->getRootLevelFolder()->getCombinedIdentifier()), $this->returnUrl);
			}
		}
		// Setting icon and title
		/** @var IconFactory $iconFactory */
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$icon = $iconFactory->getIcon('apps-filetree-root', Icon::SIZE_SMALL)->render();
		$this->title = $icon . htmlspecialchars($this->fileOrFolderObject->getStorage()->getName()) . ': ' . htmlspecialchars($this->fileOrFolderObject->getIdentifier());
		// Setting template object
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/file_rename.html');
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function backToList() {
				top.goToModule("file_FilelistList");
			}
		');
	}

	/**
	 * Main function, rendering the content of the rename form
	 *
	 * @return void
	 */
	public function main() {
		// Make page header:
		$this->content = $this->doc->startPage($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_rename.php.pagetitle'));
		$pageContent = $this->doc->header($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_rename.php.pagetitle'));
		if ($this->fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$fileIdentifier = $this->fileOrFolderObject->getCombinedIdentifier();
		} else {
			$fileIdentifier = $this->fileOrFolderObject->getUid();
		}
		$pageContent .= '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" method="post" name="editform" role="form">';
		// Making the formfields for renaming:
		$pageContent .= '

			<div class="form-group">
				<input class="form-control" type="text" name="file[rename][0][target]" value="' . htmlspecialchars($this->fileOrFolderObject->getName()) . '" ' . $this->getDocumentTemplate()->formWidth(40) . ' />
				<input type="hidden" name="file[rename][0][data]" value="' . htmlspecialchars($fileIdentifier) . '" />
			</div>
		';
		// Making submit button:
		$pageContent .= '
			<div class="form-group">
				<input class="btn btn-primary" type="submit" value="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_rename.php.submit', TRUE) . '" />
				<input class="btn btn-danger" type="submit" value="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', TRUE) . '" onclick="backToList(); return false;" />
				<input type="hidden" name="redirect" value="' . htmlspecialchars($this->returnUrl) . '" />
			</div>
		';
		$pageContent .= '</form>';
		$docHeaderButtons = array(
			'back' => ''
		);
		$docHeaderButtons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_rename');
		// Back
		if ($this->returnUrl) {
			$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
			$docHeaderButtons['back'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl($this->returnUrl)) . '" class="typo3-goBack" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', TRUE) . '">' . $iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render() . '</a>';
		}
		// Add the HTML as a section:
		$markerArray = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => '',
			'CONTENT' => $pageContent,
			'PATH' => $this->title
		);
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markerArray);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Processes the request, currently everything is handled and put together via "main()"
	 *
	 * @param ServerRequestInterface $request the current request
	 * @param ResponseInterface $response
	 * @return ResponseInterface the response with the content
	 */
	public function mainAction(ServerRequestInterface $request, ResponseInterface $response) {
		$this->main();

		$response->getBody()->write($this->content);
		return $response;
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use the mainAction() method instead
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
		echo $this->content;
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns an instance of DocumentTemplate
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

}
