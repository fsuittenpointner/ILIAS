<?php

/**
 * Class ilMMTopItemGUI
 *
 * @ilCtrl_IsCalledBy ilMMTopItemGUI: ilObjMainMenuGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 */
class ilMMTopItemGUI {

	const CMD_ADD_TOP_ITEM = 'add_slate';
	const CMD_VIEW_TOP_ITEMS = 'main_slates';
	const CMD_EDIT = 'edit_top_item';
	const CMD_TRANSLATE = 'translate_top_item';
	const CMD_UPDATE = 'update_top_item';
	const IDENTIFIER = 'identifier';
	/**
	 * @var ilToolbarGUI
	 */
	private $toolbar;
	/**
	 * @var ilMMTabHandling
	 */
	private $tab_handling;
	/**
	 * @var ilRbacSystem
	 */
	protected $rbacsystem;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilLanguage
	 */
	public $lng;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	public $tpl;
	/**
	 * @var ilTree
	 */
	public $tree;
	/**
	 * ilMMTopItemGUI constructor.
	 *
	 * @param ilMMTabHandling $tab_handling
	 */
	const CMD_TRANSLATE_TOP_ITEM = 'translate_top_item';


	public function __construct(ilMMTabHandling $tab_handling) {
		global $DIC;

		$this->tab_handling = $tab_handling;
		$this->tabs = $DIC['ilTabs'];
		$this->lng = new FakeLanguage('en');
		$this->ctrl = $DIC['ilCtrl'];
		$this->tpl = $DIC['tpl'];
		$this->tree = $DIC['tree'];
		$this->rbacsystem = $DIC['rbacsystem'];
		$this->toolbar = $DIC['ilToolbar'];
	}


	/**
	 * @return ilMMItemStorage
	 * @throws arException
	 */
	private function getMMItemFromRequest(): ilMMItemStorage {
		global $DIC;
		$var = $DIC->http()->request()->getQueryParams()[self::IDENTIFIER];
		$id = $var;
		if ($id !== 0 && $id !== '' && $id != null) {
			return ilMMItemStorage::findOrFail($id);
		}

		return new ilMMItemStorage();
	}


	private function dispatchCommand($cmd) {
		switch ($cmd) {
			case self::CMD_VIEW_TOP_ITEMS:
				$this->tab_handling->initTabs(ilObjMainMenuGUI::TAB_MAIN, $cmd);

				// ADD NEW
				$b = ilLinkButton::getInstance();
				$b->setCaption($this->lng->txt(self::CMD_ADD_TOP_ITEM), false);
				$b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD_TOP_ITEM));
				$this->toolbar->addButtonInstance($b);

				// TABLE
				$table = new ilMMTopItemTableGUI($this);

				return $table->getHTML();
			case self::CMD_ADD_TOP_ITEM:
				$this->tab_handling->initTabs(ilObjMainMenuGUI::TAB_MAIN, self::CMD_VIEW_TOP_ITEMS, true);
				global $DIC;
				$f = new ilMMTopItemFormGUI($DIC->ctrl(), $DIC->ui()->factory(), $DIC->ui()->renderer(), $this->lng, $this->getMMItemFromRequest());

				return $f->getHTML();
			case self::CMD_EDIT:
				$this->tab_handling->initTabs(ilObjMainMenuGUI::TAB_MAIN, self::CMD_VIEW_TOP_ITEMS, true);
				global $DIC;
				$f = new ilMMTopItemFormGUI($DIC->ctrl(), $DIC->ui()->factory(), $DIC->ui()->renderer(), $this->lng, $this->getMMItemFromRequest());

				return $f->getHTML();
				break;
			case self::CMD_UPDATE:
				$this->tab_handling->initTabs(ilObjMainMenuGUI::TAB_MAIN, self::CMD_VIEW_TOP_ITEMS, true);
				global $DIC;
				$f = new ilMMTopItemFormGUI($DIC->ctrl(), $DIC->ui()->factory(), $DIC->ui()->renderer(), $this->lng, $this->getMMItemFromRequest());
				$f->save();
				return $f->getHTML();
				break;
			case self::CMD_TRANSLATE_TOP_ITEM:
				$this->tab_handling->initTabs(ilObjMainMenuGUI::TAB_MAIN, self::CMD_TRANSLATE_TOP_ITEM, true);
				break;
		}

		return "";
	}


	public function executeCommand() {
		$next_class = $this->ctrl->getNextClass();

		if ($next_class == '') {
			$this->tpl->setContent($this->dispatchCommand($this->ctrl->getCmd(self::CMD_VIEW_TOP_ITEMS)));

			return;
		}

		switch ($next_class) {
			default:
				break;
		}
	}
}
