<?php
namespace TYPO3\CMS\Info\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can connect their sub-modules
 */
class InfoModuleController extends BaseScriptClass {

	/**
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Document Template Object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUser;

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $languageService;

	/**
	 * The name of the module
	 *
	 * @var string
	 */
	protected $moduleName = 'web_info';

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * ModuleTemplate Container
	 *
	 * @var ModuleTemplate
	 */
	protected $moduleTemplate;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
		$this->languageService = $GLOBALS['LANG'];
		$this->languageService->includeLLFile('EXT:lang/locallang_mod_web_info.xlf');

		$this->backendUser = $GLOBALS['BE_USER'];

		$this->MCONF = array(
			'name' => $this->moduleName,
		);
	}

	/**
	 * Initialize module header etc and call extObjContent function
	 *
	 * @return void
	 */
	public function main() {
		// We leave this here because of dependencies to submodules
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);

		// The page will show only if there is a valid page and if this page
		// may be viewed by the user
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
		$access = is_array($this->pageinfo);
		if ($this->id && $access || $this->backendUser->user['admin'] && !$this->id) {
			if ($this->backendUser->user['admin'] && !$this->id) {
				$this->pageinfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
			}
			// JavaScript
			$this->moduleTemplate->addJavaScriptCode(
				'WebFuncInLineJS',
				'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}
				');
			// Setting up the context sensitive menu:
			$this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
			$this->moduleTemplate->setForm('<form action="' . htmlspecialchars(BackendUtility::getModuleUrl($this->moduleName)) .
				'" method="post" name="webinfoForm" class="form-inline form-inline-spaced">');
			$vContent = $this->moduleTemplate->getVersionSelector($this->id, 1);
			if ($vContent) {
				$this->content .= $this->moduleTemplate->section('', $vContent);
			}
			$this->extObjContent();
			// Setting up the buttons and markers for docheader
			$this->getButtons();
			$this->generateMenu();
		} else {
			// If no access or if ID == zero
			$this->content = $this->doc->header($this->languageService->getLL('title'));
		}
	}

	/**
	 * Print module content (from $this->content)
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Injects the request object for the current request or subrequest
	 * Then checks for module functions that have hooked in, and renders menu etc.
	 *
	 * @param ServerRequestInterface $request the current request
	 * @param ResponseInterface $response
	 * @return ResponseInterface the response with the content
	 */
	public function mainAction(ServerRequestInterface $request, ResponseInterface $response) {
		$GLOBALS['SOBE'] = $this;
		$this->init();

		// Checking for first level external objects
		$this->checkExtObj();

		// Checking second level external objects
		$this->checkSubExtObj();
		$this->main();

		$this->moduleTemplate->setContent($this->content);
		$response->getBody()->write($this->moduleTemplate->renderContent());
		return $response;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 */
	protected function getButtons() {
		$buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
		// CSH
		$cshButton = $buttonBar->makeFullyRenderedButton()
			->setHtmlSource(BackendUtility::cshItem('_MOD_web_info', ''));
		$buttonBar->addButton($cshButton, ButtonBar::BUTTON_POSITION_LEFT, 0);
		// View page
		$viewButton = $buttonBar->makeLinkButton()
			->setHref('#')
			->setOnClick(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid'])))
			->setTitle($this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE))
			->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL));
		$buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
		// Shortcut
		if ($this->backendUser->mayMakeShortcut()) {
			$shortCutButton = $buttonBar->makeFullyRenderedButton()
				->setHtmlSource($this->moduleTemplate->makeShortcutIcon(
					'id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit',
					implode(',', array_keys($this->MOD_MENU)), $this->moduleName));
			$buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
		}
	}

	/**
	 * Generate the ModuleMenu
	 */
	protected function generateMenu() {
		$menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
		$menu->setIdentifier('WebInfoJumpMenu');
		foreach ($this->MOD_MENU['function'] as $controller => $title) {
			$item = $menu
				->makeMenuItem()
				->setHref(
					BackendUtility::getModuleUrl(
						$this->moduleName,
						[
							'id' => $this->id,
							'SET' => [
								'function' => $controller
							]
						]
					)
				)
				->setTitle($title);
			if ($controller === $this->MOD_SETTINGS['function']) {
				$item->setActive(TRUE);
			}
			$menu->addMenuItem($item);
		}
		$this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);

	}

}
