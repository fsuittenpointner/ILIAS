<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
* TableGUI class for TEP operation days
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id$
*
* @ingroup ServicesTEP
*/
class ilTEPOperationDaysTableGUI extends ilTable2GUI
{		
	protected $read_only; // [bool]
	protected $days; // [array]
	
	/**
	 * Constructor
	 * 
	 * @param ilTEPOperationDaysGUI $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param ilTEPOperationDays $a_operation_days
	 * @param array $a_user_ids
	 * @param bool $a_read_only
	 */
	function __construct($a_parent_obj, $a_parent_cmd, ilTEPOperationDays $a_operation_days, array $a_user_ids, $a_read_only = false)
	{
		global $ilCtrl, $lng;
		
		$this->read_only = (bool)$a_read_only;
			
		$this->setId("tepopdlist");
		
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setShowRowsSelector(true);
		$this->setTitle($lng->txt("tep_op_tab_list_operation_days"));
		
		$this->addColumn($lng->txt("name"), "name");		
		
		ilDatePresentation::setUseRelativeDates(false);
		
		$this->days = $a_operation_days->getValidDays();
		foreach($this->days as $day)
		{
			$this->addColumn(ilDatePresentation::formatDate($day));			
		}
				
		$this->setRowTemplate("tpl.operation_days_list_row.html", "Services/TEP");

		$this->setDefaultOrderField("name");
		$this->setDefaultOrderDirection("asc");
		
		if(!$this->read_only)
		{
			$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
			$this->addCommandButton("saveOperationDaysList", $lng->txt("save"));
		}
	
		$this->getItems($a_user_ids, $a_operation_days);				
	}

	function getItems(array $a_user_ids, ilTEPOperationDays $a_operation_days)
	{
		$data = array();
		
		$user_days = $a_operation_days->getDaysForUsers($a_user_ids);
		
		foreach(ilTEP::getUserNames($a_user_ids) as $user_id => $name)
		{
			$data[$user_id] = array(
				"user_id" => $user_id
				,"name" => $name
				,"days" => array()
			);			
			
			foreach($user_days[$user_id] as $day)
			{
				$data[$user_id]["days"][] = $day->get(IL_CAL_DATE);
			}
		}					
		$this->setData($data);
	}

	protected function fillRow($a_set)
	{				
		global $ilCtrl;
		
		// :TODO: test edit single user
		$ilCtrl->setParameter($this->getParentObject(), "uid", $a_set["user_id"]);
		$a_set["name"] .= ' [<a href="'.$ilCtrl->getLinkTarget($this->getParentObject(), "editUserOperationDays").'">edit test</a>]';
		$ilCtrl->setParameter($this->getParentObject(), "uid", "");
		
		$this->tpl->setVariable("VAL_NAME", $a_set["name"]);
		
		$this->tpl->setCurrentBlock("day_bl");
		foreach($this->days as $day)
		{
			$date = $day->get(IL_CAL_DATE);
			
			$this->tpl->setVariable("USER_ID", $a_set["user_id"]);
			$this->tpl->setVariable("DAY_ID", $date);
			
			if(in_array($date, $a_set["days"]))
			{
				$this->tpl->setVariable("DAY_CHECKED", ' checked="checked"');
			}
			
			if($this->read_only)
			{
				$this->tpl->setVariable("DAY_DISABLED", ' disabled="disabled"');
			}
			
			$this->tpl->parseCurrentBlock();
		}		
	}
}

?>