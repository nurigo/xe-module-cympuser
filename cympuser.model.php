<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserModel
 * @author billy(contact@nurigo.net)
 * @brief  cympuserModel
 */
class cympuserModel extends cympuser 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function triggerGetManagerMenu(&$manager_menu)
	{
		$oModuleModel = &getModel('module');

		$logged_info = Context::get('logged_info');
		$output = executeQueryArray('cympuser.getModInstList');
		if(!$output->toBool()) return $output;
		$list = $output->data;

		$menu = array();
		foreach($list as $key => $val)
		{
			$grant = $oModuleModel->getGrant($val, $logged_info);
			if($grant->manager) 
			{
				$submenu = new stdClass();
				$submenu->action = array('dispCympuserAdminMemberList');
				$submenu->mid = $val->mid;
				$submenu->title = Context::getLang('member_management');
				$submenu->module = 'cympuser';
				$manager_menu['cympusadmin']->submenu[] = $submenu;
			}
		}
	}
}
