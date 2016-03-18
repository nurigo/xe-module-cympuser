<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class cympuser 
 * @author billy(contact@nurigo.net)
 * @brief cympuser 
 */
class cympuser extends ModuleObject 
{

	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall() 
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		if (!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympuser', 'model', 'triggerGetManagerMenu', 'before')) 
		{
			$oModuleController->insertTrigger('cympusadmin.getManagerMenu', 'cympuser', 'model', 'triggerGetManagerMenu', 'before');
		}
		

	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate() 
	{
		$oModuleModel = &getModel('module');
		if (!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympuser', 'model', 'triggerGetManagerMenu', 'before')) return true;

		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate() 
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if (!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'cympuser', 'model', 'triggerGetManagerMenu', 'before')) {
			$oModuleController->insertTrigger('cympusadmin.getManagerMenu', 'cympuser', 'model', 'triggerGetManagerMenu', 'before');
		}

	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache() 
	{
	}
}
?>
