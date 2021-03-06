<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;

/**
 * Controller for extension listings (TER or local extensions)
 */
class ListController extends AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
	 */
	protected $dependencyUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility
	 */
	protected $configurationUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
	 */
	public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility
	 */
	public function injectDependencyUtility(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility) {
		$this->dependencyUtility = $dependencyUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility $configurationUtility
	 */
	public function injectConfigurationUtility(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility $configurationUtility) {
		$this->configurationUtility = $configurationUtility;
	}

	/**
	 * Add the needed JavaScript files for all actions
	 */
	public function initializeAction() {
		$this->pageRenderer->addInlineLanguageLabelFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
		if ($this->configurationUtility->getCurrentConfiguration('extensionmanager')['offlineMode']['value']) {
			$this->settings['offlineMode'] = TRUE;
		}
	}

	/**
	 * Adds an information about composer mode
	 */
	protected function addComposerModeNotification() {
		if (Bootstrap::usesComposerClassLoading()) {
			$this->addFlashMessage(
				LocalizationUtility::translate(
					'composerMode.message',
					'extensionmanager'
				),
				LocalizationUtility::translate(
					'composerMode.title',
					'extensionmanager'
				),
				FlashMessage::WARNING
			);
		}
	}

	/**
	 * Shows list of extensions present in the system
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->addComposerModeNotification();
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
		$this->view->assign('extensions', $availableAndInstalledExtensions);
		$this->handleTriggerArguments();
	}

	/**
	 * Shows a list of unresolved dependency errors with the possibility to bypass the dependency check
	 *
	 * @param string $extensionKey
	 * @throws ExtensionManagerException
	 * @return void
	 */
	public function unresolvedDependenciesAction($extensionKey) {
		$availableExtensions = $this->listUtility->getAvailableExtensions();
		if (isset($availableExtensions[$extensionKey])) {
			$extensionArray = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
				array(
					$extensionKey => $availableExtensions[$extensionKey]
				)
			);
			/** @var ExtensionModelUtility $extensionModelUtility */
			$extensionModelUtility = $this->objectManager->get(ExtensionModelUtility::class);
			$extension = $extensionModelUtility->mapExtensionArrayToModel($extensionArray[$extensionKey]);
		} else {
			throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1402421007);
		}
		$this->dependencyUtility->checkDependencies($extension);
		$this->view->assign('extension', $extension);
		$this->view->assign('unresolvedDependencies', $this->dependencyUtility->getDependencyErrors());
	}

	/**
	 * Shows extensions from TER
	 * Either all extensions or depending on a search param
	 *
	 * @param string $search
	 * @return void
	 */
	public function terAction($search = '') {
		$this->addComposerModeNotification();
		$search = trim($search);
		if (!empty($search)) {
			$extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
		} else {
			$extensions = $this->extensionRepository->findAll();
		}
		$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($this->listUtility->getAvailableExtensions());
		$this->view->assign('extensions', $extensions)
				->assign('search', $search)
				->assign('availableAndInstalled', $availableAndInstalledExtensions);
	}

	/**
	 * Action for listing all possible distributions
	 *
	 * @param bool $showUnsuitableDistributions
	 * @return void
	 */
	public function distributionsAction($showUnsuitableDistributions = FALSE) {
		$this->addComposerModeNotification();
		$importExportInstalled = ExtensionManagementUtility::isLoaded('impexp');
		if ($importExportInstalled) {
			try {
				/** @var $repositoryHelper Helper */
				$repositoryHelper = $this->objectManager->get(Helper::class);
				// Check if a TER update has been done at all, if not, fetch it directly
				// Repository needs an update, but not because of the extension hash has changed
				$isExtListUpdateNecessary = $repositoryHelper->isExtListUpdateNecessary();
				if ($isExtListUpdateNecessary > 0 && ($isExtListUpdateNecessary & $repositoryHelper::PROBLEM_EXTENSION_HASH_CHANGED) === 0) {
					$repositoryHelper->updateExtList();
				}
			} catch (ExtensionManagerException $e) {
				$this->addFlashMessage($e->getMessage(), $e->getCode(), FlashMessage::ERROR);
			}

			$officialDistributions = $this->extensionRepository->findAllOfficialDistributions();
			$communityDistributions = $this->extensionRepository->findAllCommunityDistributions();

			if (!$showUnsuitableDistributions) {
				$suitableOfficialDistributions = $this->dependencyUtility->getExtensionsSuitableForTypo3Version($officialDistributions);
				$this->view->assign('officialDistributions', $suitableOfficialDistributions);
				$suitableCommunityDistributions = $this->dependencyUtility->getExtensionsSuitableForTypo3Version($communityDistributions);
				$this->view->assign('communityDistributions', $suitableCommunityDistributions);
			} else {
				$this->view->assign('officialDistributions', $officialDistributions);
				$this->view->assign('communityDistributions', $communityDistributions);
			}
		}
		$this->view->assign('enableDistributionsView', $importExportInstalled);
		$this->view->assign('showUnsuitableDistributions', $showUnsuitableDistributions);
	}

	/**
	 * Shows all versions of a specific extension
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function showAllVersionsAction($extensionKey) {
		$currentVersion = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extensionKey);
		$extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);

		$this->view->assignMultiple(
			array(
				'extensionKey' => $extensionKey,
				'currentVersion' => $currentVersion,
				'extensions' => $extensions
			)
		);
	}

}
