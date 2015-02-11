<?php

class WSAL_Sensors_LogInOut extends WSAL_AbstractSensor {

	public function HookEvents() {
		add_action('wp_login', array($this, 'EventLogin'), 10, 2);
		add_action('wp_logout', array($this, 'EventLogout'));
		add_action('wp_login_failed', array($this, 'EventLoginFailure'));
		add_action('clear_auth_cookie', array($this, 'GetCurrentUser'), 10);
	}
	
	protected $_current_user = null;
	
	public function GetCurrentUser(){
		$this->_current_user = wp_get_current_user();
	}
	
	public function EventLogin($user_login, $user){
		$this->plugin->alerts->Trigger(1000, array(
			'Username' => $user_login,
			'CurrentUserRoles' => $this->plugin->settings->GetCurrentUserRoles($user->roles),
		), true);
	}
	
	public function EventLogout(){
		if($this->_current_user->ID != 0){
			$this->plugin->alerts->Trigger(1001, array(
				'CurrentUserID' => $this->_current_user->ID,
				'CurrentUserRoles' => $this->plugin->settings->GetCurrentUserRoles($this->_current_user->roles),
			), true);
		}
	}
	
	const TRANSIENT_FAILEDLOGINS = 'wsal-failedlogins-known';
	const TRANSIENT_FAILEDLOGINS_UNKNOWN = 'wsal-failedlogins-unknown';
	
	protected function GetLoginFailureLogLimit(){
		return 10;
	}
	
	protected function GetLoginFailureExpiration(){
		return 12 * 60 * 60;
	}
	
	protected function IsPastLoginFailureLimit($ip, $type){
		if($type == "1002") {
			$data = get_transient(self::TRANSIENT_FAILEDLOGINS);
			return ($data !== false) && isset($data[$ip]) && ($data[$ip] > $this->GetLoginFailureLogLimit());
		} else {
			$data2 = get_transient(self::TRANSIENT_FAILEDLOGINS_UNKNOWN);
			return ($data2 !== false) && isset($data2[$ip]) && ($data2[$ip] > $this->GetLoginFailureLogLimit());
		}
	}
	
	protected function IncrementLoginFailure($ip, $type){
		if($type == "1002") {
			$data = get_transient(self::TRANSIENT_FAILEDLOGINS);
			if(!$data)$data = array();
			if(!isset($data[$ip]))$data[$ip] = 0;
			$data[$ip]++;
			set_transient(self::TRANSIENT_FAILEDLOGINS, $data, $this->GetLoginFailureExpiration());
		} else {
			$data2 = get_transient(self::TRANSIENT_FAILEDLOGINS_UNKNOWN);
			if(!$data2)$data2 = array();
			if(!isset($data2[$ip]))$data2[$ip] = 0;
			$data2[$ip]++;
			set_transient(self::TRANSIENT_FAILEDLOGINS_UNKNOWN, $data2, $this->GetLoginFailureExpiration());
		}
	}
	
	public function EventLoginFailure($username){
		
		list($y, $m, $d) = explode('-', date('Y-m-d'));
		
		$ip = $this->plugin->settings->GetMainClientIP();
		$tt1 = new WSAL_DB_Occurrence();
		$tt2 = new WSAL_DB_Meta();
		
		$username = $_POST["log"];
		$newAlertCode = 1003;
		$user = get_user_by('login', $username);
		if ($user) {
			$newAlertCode = 1002;	
			$userRoles = $this->plugin->settings->GetCurrentUserRoles($user->roles);
		}

		if($this->IsPastLoginFailureLimit($ip, $newAlertCode))return;

		$occ = WSAL_DB_Occurrence::LoadMultiQuery('
			SELECT occurrence.* FROM `' . $tt1->GetTable() . '` occurrence 
			INNER JOIN `' . $tt2->GetTable() . '` ipMeta on ipMeta.occurrence_id = occurrence.id
			and ipMeta.name = "ClientIP"
			and ipMeta.value = %s
			INNER JOIN `' . $tt2->GetTable() . '` usernameMeta on usernameMeta.occurrence_id = occurrence.id
			and usernameMeta.name = "Username"
			and usernameMeta.value = %s
			WHERE occurrence.alert_id = %d AND occurrence.site_id = %d
			AND (created_on BETWEEN %d AND %d)
			GROUP BY occurrence.id',
			array(
				json_encode($ip),
				json_encode($username),
				1002,
				(function_exists('get_current_blog_id') ? get_current_blog_id() : 0),
				mktime(0, 0, 0, $m, $d, $y),
				mktime(0, 0, 0, $m, $d + 1, $y) - 1
			)
		);

		$occ = count($occ) ? $occ[0] : null;
		
		if($occ && $occ->IsLoaded()){
			// update existing record exists user
			$new = $occ->GetMetaValue('Attempts', 0) + 1;
			
			if($new > $this->GetLoginFailureLogLimit())
				$new = $this->GetLoginFailureLogLimit() . '+';
			
			$occ->SetMetaValue('Attempts', $new);
			$occ->SetMetaValue('Username', $username);
			//$occ->SetMetaValue('CurrentUserRoles', $userRoles);
			$occ->created_on = null;
			$occ->Save();
			$this->IncrementLoginFailure($ip, $newAlertCode);
		} else {
			if ($newAlertCode == 1002) {
				// create a new record exists user
				$this->plugin->alerts->Trigger($newAlertCode, array(
					'Attempts' => 1,
					'Username' => $_POST["log"],
					'CurrentUserRoles' => $userRoles
				));
			} else {
				$occUnknown = WSAL_DB_Occurrence::LoadMultiQuery('
					SELECT occurrence.* FROM `' . $tt1->GetTable() . '` occurrence 
					INNER JOIN `' . $tt2->GetTable() . '` ipMeta on ipMeta.occurrence_id = occurrence.id 
					and ipMeta.name = "ClientIP" and ipMeta.value = %s 
					WHERE occurrence.alert_id = %d 
					AND (created_on BETWEEN %d AND %d)
					GROUP BY occurrence.id',
					array(
						json_encode($ip),
						1003,
						mktime(0, 0, 0, $m, $d, $y),
						mktime(0, 0, 0, $m, $d + 1, $y) - 1
					)
				);
				
				$occUnknown = count($occUnknown) ? $occUnknown[0] : null;
				if($occUnknown && $occUnknown->IsLoaded()) {
					// update existing record not exists user
					$new = $occUnknown->GetMetaValue('Attempts', 0) + 1;
					
					if($new > $this->GetLoginFailureLogLimit())
						$new = $this->GetLoginFailureLogLimit() . '+';
					
					$occUnknown->SetMetaValue('Attempts', $new);
					$occUnknown->created_on = null;
					$occUnknown->Save();
					$this->IncrementLoginFailure($ip, $newAlertCode);
				} else {
					// create a new record not exists user
					$this->plugin->alerts->Trigger($newAlertCode, array('Attempts' => 1));
				}
			}
		}
	}
	
}
