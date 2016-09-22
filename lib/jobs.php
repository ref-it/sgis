<?php

namespace OCA\user_sgis\lib;

class Jobs extends \OC\BackgroundJob\TimedJob {
	public function __construct(){
		$this->interval = self::getRefreshInterval();
	}

	/**
	 * @param mixed $argument
	 */
	public function run($argument){
		\OCP\Util::writeLog('user_sgis', 'Run background job', \OCP\Util::DEBUG);

		if (!\OCP\App::isEnabled('user_sgis')) return;
		Jobs::updateGroups();
	}

	static public function updateGroups() {	
		\OCP\Util::writeLog('user_sgis', 'Run background job "updateGroups"', \OCP\Util::DEBUG);

		$be = \OC_USER_SGIS::getMe();
		\OC_User::clearBackends();
		\OC_User::useBackend($be);
		$users = $be->getUsers();

		foreach ($users as $uid) {
			\OCP\Util::writeLog('user_sgis', "test $uid", \OCP\Util::DEBUG);
			$be->userExists($uid);
		}

		\OCP\Util::writeLog('user_sgis', 'Done run background job "updateGroups"', \OCP\Util::DEBUG);
	}

	/**
	 * @return int
	 */
	static private function getRefreshInterval() {
		//defaults to every hour
		return \OCP\Config::getAppValue('user_sgis', 'bgjRefreshInterval', 3600);
	}
}
