<?php
/**
 * Class: WSAL\Models\Options
 *
 * Option Model gets and sets the options from the main WP options table.
 *
 * @since   4.0.2
 * @package Wsal
 */

namespace WSAL\Helpers;

/**
 * WordPress options are always loaded from the default WordPress database.
 *
 * NOTE: there is primarily a wrapper class around core functions and it has
 * no cache layer here as wp has an internal options cache of it's own.
 *
 * @since 4.0.2
 */
class Options {

	/**
	 * Instance of the main plugin class.
	 *
	 * @since 4.0.2
	 * @var   WpSecurityAuditLog
	 */
	private $plugin;

	/**
	 * Prefix used when setting/getting options.
	 *
	 * @since 4.0.2
	 * @var   @var string
	 */
	public $prefix;

	/**
	 * Sets up this class with the main plugin instance and a prefix.
	 *
	 * @method __construct
	 * @since  4.0.2
	 * @param  WpSecurityAuditLog $plugin The main plugin class instance.
	 * @param  string             $prefix A prefix to use when setting/getting.
	 */
	public function __construct( $plugin, $prefix = '' ) {
		// the main plugin file incase we need to get data from it.
		$this->plugin = $plugin;
		// sets the prefix used when getting all options through this class.
		$this->set_prefix( $prefix );
	}

	/**
	 * Setter to allow changing prefixes when class isn't fetching own options.
	 *
	 * @method set_prefix
	 * @since  4.0.2
	 * @param  string $prefix The prefix string to use when fetching.
	 */
	public function set_prefix( $prefix = '' ) {
		$this->prefix = ( is_string( $prefix ) ) ? $prefix : '';
	}

	/**
	 * Gets the value of an option.
	 *
	 * @method get_option_value
	 * @since  4.0.2
	 * @param  string $option_name option name we want to get a value for.
	 * @param  mixed  $default     a default value to use when one doesn't exist.
	 * @return mixed
	 */
	public function get_option_value( $option_name = '', $default = null ) {
		// bail early if no option name was requested.
		if ( empty( $option_name ) || ! is_string( $option_name ) ) {
			return;
		}

		$actual_option_name = $option_name;
		if (!preg_match( '/\A' .preg_quote($this->prefix) . '/', $option_name)) {
			//  remove prefix duplicate if present
			$actual_option_name = $this->prefix . $option_name;
		}

		return self::_get_option_value( $actual_option_name, $default);
	}

	/**
	 * Sets the value of an option.
	 *
	 * @method set_option_value
	 * @since  4.0.2
	 * @param  string $option_name The name of option to save.
	 * @param  mixed  $value       A value to store under the option name.
	 * @param  bool   $autoload    Whether or not to autoload this option.
	 * @return bool Whether or not the option was updated.
	 */
	public function set_option_value( $option_name = '', $value = null, $autoload = true ) {
		// bail early if no option name or value was passed.
		if ( empty( $option_name ) || null === $value ) {
			return;
		}

		$actual_option_name = $option_name;
		if (preg_match( '/\A' .preg_quote(\WpSecurityAuditLog::OPT_PRFX) . '/', $option_name)) {
			//  remove legacy prefix
			$actual_option_name = substr($option_name, strlen(\WpSecurityAuditLog::OPT_PRFX));
		}

		if (!preg_match( '/\A' .preg_quote($this->prefix) . '/', $option_name)) {
			//  prepend prefix if not already present
			$actual_option_name = $this->prefix . $option_name;
		}

		$result = self::_set_option_value($actual_option_name, $value, $autoload);

		return $result;
	}

	/**
	 * Deletes an option from the WP options table.
	 *
	 * NOTE: This is just a straight wrapper around the core function - if the
	 * item is prefixed then pass the prefix in the option name.
	 *
	 * @method delete_option
	 * @since  4.0.2
	 * @param  string $option_name Name of the option to delete.
	 * @return bool
	 */
	public function delete_option( $option_name = '' ) {
		if (is_multisite()) {
			switch_to_blog(get_main_network_id());
		}

		$result = \delete_option( $option_name );

		if (is_multisite()) {
			restore_current_blog();
		}

		return $result;
	}

	/**
	 * Get options by prefix (notifications stored in json format).
	 *
	 * @param string $opt_prefix - Prefix.
	 * @return array|null - Options.
	 */
	public function GetNotificationsSetting( $opt_prefix ) {
		global $wpdb;
		$prepared_query	= $wpdb->prepare(
		"SELECT * FROM {$wpdb->options} WHERE option_name LIKE %s;",
		$opt_prefix . '%%'
		);
		return $wpdb->get_results($prepared_query);
	}

	/**
	 * @param int $id Notification ID.
	 *
	 * @return array|object|void|null
	 * @since 4.1.3
	 */
	public function GetNotification($id) {
		global $wpdb;
		$prepared_query = $wpdb->prepare("SELECT * FROM {$wpdb->options} WHERE option_id = %d LIMIT 1;", $id);
		return $wpdb->get_row( $prepared_query );
	}

	/**
	 * Number of options start with prefix.
	 *
	 * @param string $opt_prefix - Prefix.
	 * @return integer Indicates the number of items.
	 */
	public function CountNotifications( $opt_prefix ) {
		global $wpdb;

		$prepared_query	= $wpdb->prepare(
			"SELECT COUNT(option_id) FROM {$wpdb->options} WHERE option_name LIKE %s;",
			$opt_prefix . '%%'
		);
		return (int) $wpdb->get_var( $prepared_query );

	}

	/**
	 * Static function for retrieving an option value statically.
	 *
	 * WARNING!
	 * ========
	 * This should be used only when absolutely necessary. For example in very early stages of WordPress application
	 * lifecycle before the whole plugin is loaded. At the time of writing this function, only frontend events settings
	 * was needed to be treated this way.
	 *
	 * In all other cases function \WpSecurityAuditLog::GetGlobalSetting() should be used instead.
	 *
	 * @see \WpSecurityAuditLog::GetGlobalSetting()
	 * @since  4.1.3
	 * @param  string $option_name Option name we want to get a value for including necessary plugin prefix.
	 * @param  mixed  $default     a default value to use when one doesn't exist.
	 * @return mixed
	 */
	public static function get_option_value_ignore_prefix( $option_name = '', $default = null) {
		return self::_get_option_value($option_name, $default);
	}

	/**
	 * Internal function used to get the value of an option. Any necessary prefixes are already contained in the option
	 * name.
	 *
	 * @since  4.1.3
	 * @param  string $option_name Option name we want to get a value for including necessary plugin prefix.
	 * @param  mixed  $default     a default value to use when one doesn't exist.
	 * @return mixed
	 */
	private static function _get_option_value( $option_name = '', $default = null ) {
		// bail early if no option name was requested.
		if ( empty( $option_name ) || ! is_string( $option_name ) ) {
			return;
		}

		if (is_multisite()) {
			switch_to_blog(get_main_network_id());
		}

		$result = \get_option( $option_name, $default );

		if (is_multisite()) {
			restore_current_blog();
		}
		return maybe_unserialize($result);
	}

	/**
	 * Static function for saving an option value statically.
	 *
	 * WARNING!
	 * ========
	 * This should be used only when absolutely necessary. For example in very early stages of WordPress application
	 * lifecycle before the whole plugin is loaded. At the time of writing this function, only frontend events settings
	 * was needed to be treated this way.
	 *
	 * In all other cases function \WpSecurityAuditLog::SetGlobalSetting() should be used instead.
	 *
	 * @see \WpSecurityAuditLog::SetGlobalSetting()
	 * @since  4.1.3
	 * @param  string $option_name Option name we want to get a value for including necessary plugin prefix.
	 * @param  mixed  $value     A value to store under the option name.
	 * @param  bool   $autoload    Whether or not to autoload this option.
	 * @return mixed
	 */
	public static function set_option_value_ignore_prefix( $option_name = '', $value = null, $autoload = true ) {
		return self::_set_option_value( $option_name, $value, $autoload = true );
	}

	/**
	 * Internal function used to set the value of an option. Any necessary prefixes are already contained in the option
	 * name.
	 *
	 * @since  4.1.3
	 * @param  string $option_name Option name we want to save a value for including necessary plugin prefix.
	 * @param  mixed  $value       A value to store under the option name.
	 * @param  bool   $autoload    Whether or not to autoload this option.
	 * @return bool Whether or not the option was updated.
	 */
	private static function _set_option_value( $option_name = '', $value = null, $autoload = true ) {
		// bail early if no option name or value was passed.
		if ( empty( $option_name ) || null === $value ) {
			return;
		}

		if (is_multisite()) {
			switch_to_blog(get_main_network_id());
		}

		$result = \update_option( $option_name, $value, $autoload );

		if (is_multisite()) {
			restore_current_blog();
		}

		return $result;
	}

	/**
	 * Converts a string (e.g. 'yes' or 'no') to a bool.
	 *
	 * @since 4.1.3
	 * @param string $string String to convert.
	 * @return bool
	 */
	public static function string_to_bool( $string ) {
		return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string || 'on' === $string || 'enable' === $string);
	}

	/**
	 * Converts a bool to a 'yes' or 'no'.
	 *
	 * @since 4.1.3
	 * @param bool $bool String to convert.
	 * @return string
	 */
	public static function bool_to_string( $bool ) {
		if ( ! is_bool( $bool ) ) {
			$bool = self::string_to_bool( $bool );
		}
		return true === $bool ? 'yes' : 'no';
	}
}
