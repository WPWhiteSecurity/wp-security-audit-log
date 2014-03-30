<?php

final class WSAL_AlertManager {
	
	/**
	 * @var WSAL_Alert[]
	 */
	protected $_alerts = array();
	
	/**
	 * @var WSAL_AbstractLogger[]
	 */
	protected $_loggers = array();
	
	/**
	 * @var WpSecurityAuditLog
	 */
	protected $plugin;
	
	/**
	 * Disabled alerts option name.
	 */
	const OPT_DISABLED_TYPES = 'wsal-disabled-alerts';
	
	/**
	 * Create new AlertManager instance.
	 * @param WpSecurityAuditLog $plugin
	 */
	public function __construct(WpSecurityAuditLog $plugin){
		$this->plugin = $plugin;
		foreach(glob(dirname(__FILE__) . '/Loggers/*.php') as $file){
			$class = $plugin->GetClassFileClassName($file);
			$this->_loggers[] = new $class($plugin);
		}
	}
	
	/**
	 * Trigger an alert.
	 * @param integer $type Alert type.
	 * @param array $data Alert data.
	 */
	public function Trigger($type, $data = array()){
		if($this->IsEnabled($type)){
			if(isset($this->_alerts[$type])){
				// ok, convert alert to a log entry
				$this->Log($type, $data);
			}else{
				// in general this shouldn't happen, but it could, so we handle it here :)
				throw new Exception('Alert with code "' . $type . '" has not be registered.');
			}
		}
	}
	
	/**
	 * Register an alert type.
	 * @param array $info Array of [type, code, category, description, message] respectively.
	 */
	public function Register($info){
		if(func_num_args() == 1){
			// handle single item
			list($type, $code, $catg, $desc, $mesg) = $info;
			if(isset($this->_alerts[$type]))
				throw new Exception("Alert $type already registered with Alert Manager.");
			$this->_alerts[$type] = new WSAL_Alert($type, $code, $catg, $desc, $mesg);
		}else{
			// handle multiple items
			foreach(func_get_args() as $arg)
				$this->Register($arg);
		}
	}
	
	/**
	 * Register a whole group of items.
	 * @param array $items An array with group name as the index and an array of group items as the value.
	 * Item values is an array of [type, code, description, message] respectively.
	 */
	public function RegisterGroup($groups){
		foreach($groups as $name => $group){
			foreach($group as $item){
				list($type, $code, $desc, $mesg) = $item;
				$this->Register(array($type, $code, $name, $desc, $mesg));
			}
		}
	}
	
	protected $_disabled = null;
	
	/**
	 * Returns whether alert of type $type is enabled or not.
	 * @param integer $type Alert type.
	 * @return boolean True if enabled, false otherwise.
	 */
	public function IsEnabled($type){
		return !in_array($type, $this->GetDisabledAlerts());
	}
	
	/**
	 * Disables a set of alerts by type.
	 * @param int[] $types Alert type codes to be disabled.
	 */
	public function SetDisabledAlerts($types){
		$this->_disabled = array_unique(array_map('intval', $types));
		update_option(self::OPT_DISABLED_TYPES, implode(',', $this->_disabled));
	}
	
	/**
	 * @return int[] Returns an array of disabled alerts' type code.
	 */
	public function GetDisabledAlerts(){
		if(!$this->_disabled){
			$this->_disabled = get_option(self::OPT_DISABLED_TYPES, ',');
			$this->_disabled = explode(',', $this->_disabled);
			$this->_disabled = array_map('intval', $this->_disabled);
		}
		return $this->_disabled;
	}
	
	/**
	 * Converts an Alert into a Log entry (by invoking loggers).
	 * You should not call this method directly.
	 * @param integer $type Alert type.
	 * @param array $data Misc alert data.
	 */
	protected function Log($type, $data = array()){
		if(!isset($data['ClientIP']))
			$data['ClientIP'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		if(!isset($data['UserAgent']))
			$data['UserAgent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		if(!isset($data['CurrentUserID']))
			$data['CurrentUserID'] = function_exists('get_current_user_id') ? get_current_user_id() : 0;
		if(!isset($data['CurrentBlogID']))
			$data['CurrentBlogID'] = function_exists('get_current_blog_id') ? get_current_blog_id() : 1;
		
		foreach($this->_loggers as $logger)
			$logger->Log($type, $data);
	}
	
	/**
	 * Return alert given alert type.
	 * @param integer $type Alert type.
	 * @param mixed $default Returned if alert is not found.
	 * @return WSAL_Alert
	 */
	public function GetAlert($type, $default = null){
		foreach($this->_alerts as $alert)
			if($alert->type == $type)
				return $alert;
		return $default;
	}
	
	/**
	 * Returns all supported alerts.
	 * @return WSAL_Alert[]
	 */
	public function GetAlerts(){
		return $this->_alerts;
	}
	
	/**
	 * Returns all supported alerts.
	 * @return array
	 */
	public function GetCategorizedAlerts(){
		$result = array();
		foreach($this->_alerts as $alert){
			if(!isset($result[$alert->catg]))
				$result[$alert->catg] = array();
			$result[$alert->catg][] = $alert;
		}
		ksort($result);
		return $result;
	}
	
}