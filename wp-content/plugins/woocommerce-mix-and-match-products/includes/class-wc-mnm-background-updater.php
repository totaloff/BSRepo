<?php
/**
 * WC_MNM_Background_Updater class
 *
 * @since  1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'libraries/wp-async-request.php' );
include_once( 'libraries/wp-background-process.php' );

/**
 * Background Updater.
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle DB
 * updates in the background.
 *
 * @class    WC_MNM_Background_Updater
 * @version  1.2.0
 */
class WC_MNM_Background_Updater extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'wc_mnm_updater';

	/**
	 * Dispatch updater.
	 */
	public function dispatch() {

		$dispatched = parent::dispatch();

		if ( is_wp_error( $dispatched ) ) {
			WC_MNM_Core_Compatibility::log( sprintf( 'Unable to dispatch WooCommerce Mix and Match Products updater: %s', $dispatched->get_error_message() ), 'error', 'wc_mnm_db_updates' );
		}
	}

	/**
	 * Handle cron healthcheck.
	 *
	 * Restart the background process if not already running and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			return;
		}

		$this->handle();
	}

	/**
	 * Schedule event.
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater queue empty?
	 *
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}

	/**
	 * Is the updater actually running?
	 *
	 * @return boolean
	 */
	public function is_process_running() {
		return parent::is_process_running();
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	public function time_exceeded() {
		return parent::time_exceeded();
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	public function memory_exceeded() {
		return parent::memory_exceeded();
	}

	/**
	 * Runs update task and creates log entries.
	 *
	 * @param  string  $callback
	 * @return mixed
	 */
	protected function task( $callback ) {

		include_once( 'wc-mnm-update-functions.php' );

		if ( is_callable( $callback ) ) {

			WC_MNM_Core_Compatibility::log( sprintf( '- Running %s callback...', $callback ), 'info', 'wc_mnm_db_updates' );

			$result = call_user_func_array( $callback, array( $this ) );

			if ( -1 === $result ) {
				$message = sprintf( '- Restarting %s callback.', $callback );
				// Add this to ensure the task gets restarted right away.
				add_filter( 'wp_wc_mnm_updater_time_exceeded', '__return_true' );
			} elseif ( -2 === $result ) {
				$message = sprintf( '- Requeuing %s callback.', $callback );
			} else {
				$message = sprintf( '- Finished %s callback.', $callback );
			}

			WC_MNM_Core_Compatibility::log( $message, 'info', 'wc_mnm_db_updates' );

		} else {
			WC_MNM_Core_Compatibility::log( sprintf( '- Could not find %s callback.', $callback ), 'notice', 'wc_mnm_db_updates' );
		}

		return in_array( $result, array( -1, -2 ) ) ? $callback : false;
	}

	/**
	 * When all tasks complete, update plugin db version and create log entry.
	 */
	protected function complete() {
		WC_MNM_Install::update_complete();
		parent::complete();
	}
}
