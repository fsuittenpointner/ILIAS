<?php
require_once 'Customizing/global/plugins/Services/Cron/CronHook/ReportMaster/classes/ReportSettings/class.setting.php';

class settingRichText extends setting {
	/**
	 * @inheritdoc
	 */
	protected function defaultDefaultValue() {
		return "";
	}

	/**
	 * @inheritdoc
	 */
	protected function defaultToForm() {
		return function($val) {return $val;};
	}

	/**
	 * @inheritdoc
	 */
	protected function defaultFromForm() {
		return function($val) {return $val;};
	}
}