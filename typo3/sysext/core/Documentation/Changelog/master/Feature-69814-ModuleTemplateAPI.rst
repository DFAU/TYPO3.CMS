==================
ModuleTemplate API
==================

Challenge
=========


Currently all DocHeaders are implemented on their own.

This means we have about 80 DocHeaders which are equal but not the same.

The main challenge is to provide extension developers with all tools they need to build decent backend modules while maintaining control of the docHeader itself.

Solution
========

We will provide a replacement for DocumentTemplate which provides an easy-to-use API which is on the other hand flexible enough to tackle all tasks we currently think of.

At the same time we will remove the amount of duplicate marker based templates.

The API uses the Fluent-API approach and has been built to supply maximum IDE code completion support.

Parts of a docHeader Currently a typical docHeader is split up into the following sections:

* Top Bar

  * Context Sensitive Help Icon
  * Select Menu(s)
  * Path
  * RecordInformation incl. Clickmenu

* Bottom Bar

  * Left Button Bar
  * Right Button Bar

API Components
==============

Buttons
-------

**InputButton**
    Used to generate a <button> element.

**LinkButton**
    Used to generate links

**SplitButton**
    A mixed component accepting multiple button objects and renders them into a condensed form.

**FullyRenderedButton**
    Displays arbitrary HTML code and we highly recommend to use these.

Menus
-----

Creating menus is pretty simple.
Ask the ``DocHeaderComponent`` for the ``MenuRegistry`` and ask the ``MenuRegistry`` to create a ``Menu`` for you.

The ``Menu`` in return can create ``MenuItems`` for you.

A ``Menu`` can have several **Types** which are represented by their respective Fluid Partials in EXT:backend/Resources/Private/Partials/Menu/.


Examples of usages
==================

**Adding a button**

.. code-block:: php

    $openInNewWindowButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()
        ->makeLinkButton()
        ->setHref('#')
        ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.openInNewWindow', TRUE))
        ->setIcon($this->iconFactory->getIcon('actions-window-open', Icon::SIZE_SMALL))
        ->setOnClick($aOnClick);

    $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()
        ->addButton($openInNewWindowButton, ButtonBar::BUTTON_POSITION_RIGHT);

**Adding a menu with menu items**

.. code-block:: php

    $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getModuleMenuRegistry()->makeMenu()
        ->setIdentifier('_langSelector')
        ->setLabel($this->getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.language', TRUE));
    $menuItem = $languageMenu->makeMenuItem()
        ->setTitle($lang['title'] . $newTranslation)
        ->setHref($href);
    if((int)$lang['uid'] === $currentLanguage) {
        $menuItem->setActive(TRUE);
    }
    $languageMenu->addMenuItem($menuItem);
    $this->moduleTemplate->getDocHeaderComponent()->getModuleMenuRegistry()->addMenu($languageMenu);