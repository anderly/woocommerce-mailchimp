<?php

/**
 * Class ActionScheduler_wpCommentLogger
 */
class ActionScheduler_wpCommentLogger extends ActionScheduler_Logger {
	const AGENT = 'ActionScheduler';
	const TYPE = 'action_log';

	/**
	 * @param string $action_id
	 * @param string $message
	 * @param DateTime $date
	 *
	 * @return string The log entry ID
	 */
	public function log( $action_id, $message, DateTime $date = NULL ) {
		if ( empty($date) ) {
			$date = new DateTime();
		} else {
			$date = clone( $date );
		}
		$comment_id = $this->create_wp_comment( $action_id, $message, $date );
		return $comment_id;
	}

	protected function create_wp_comment( $action_id, $message, DateTime $date ) {
		$date->setTimezone( ActionScheduler_TimezoneHelper::get_local_timezone() );
		$comment_data = array(
			'comment_post_ID' => $action_id,
			'comment_date' => $date->format('Y-m-d H:i:s'),
			'comment_author' => self::AGENT,
			'comment_content' => $message,
			'comment_agent' => self::AGENT,
			'comment_type' => self::TYPE,
		);
		return wp_insert_comment($comment_data);
	}

	/**
	 * @param string $entry_id
	 *
	 * @return ActionScheduler_LogEntry
	 */
	public function get_entry( $entry_id ) {
		$comment = $this->get_comment( $entry_id );
		if ( empty($comment) || $comment->comment_type != self::TYPE ) {
			return new ActionScheduler_NullLogEntry();
		}
		return new ActionScheduler_LogEntry( $comment->comment_post_ID, $comment->comment_content, $comment->comment_type );
	}

	/**
	 * @param string $action_id
	 *
	 * @return ActionScheduler_LogEntry[]
	 */
	public function get_logs( $action_id ) {
		$status = 'all';
		if ( get_post_status($action_id) == 'trash' ) {
			$status = 'post-trashed';
		}
		$comments = get_comments(array(
			'post_id' => $action_id,
			'orderby' => 'comment_date_gmt',
			'order' => 'ASC',
			'type' => self::TYPE,
			'status' => $status,
		));
		$logs = array();
		foreach ( $comments as $c ) {
			$entry = $this->get_entry( $c );
			if ( !empty($entry) ) {
				$logs[] = $entry;
			}
		}
		return $logs;
	}

	protected function get_comment( $comment_id ) {
		return get_comment( $comment_id );
	}



	/**
	 * @param WP_Comment_Query $query
	 *
	 * @return void
	 */
	public function filter_comment_queries( $query ) {
		foreach ( array('ID', 'parent', 'post_author', 'post_name', 'post_parent', 'type', 'post_type', 'post_id', 'post_ID') as $key ) {
			if ( !empty($query->query_vars[$key]) ) {
				return; // don't slow down queries that wouldn't include action_log comments anyway
			}
		}
		$query->query_vars['action_log_filter'] = TRUE;
		add_filter( 'comments_clauses', array( $this, 'filter_comment_query_clauses' ), 10, 2 );
	}

	/**
	 * @param array $clauses
	 * @param WP_Comment_Query $query
	 *
	 * @return array
	 */
	public function filter_comment_query_clauses( $clauses, $query ) {
		if ( !empty($query->query_vars['action_log_filter']) ) {
			global $wpdb;
			$clauses['where'] .= sprintf(" AND {$wpdb->comments}.comment_type != '%s'", self::TYPE);
		}
		return $clauses;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		add_action( 'action_scheduler_before_process_queue', array( $this, 'disable_comment_counting' ), 10, 0 );
		add_action( 'action_scheduler_after_process_queue', array( $this, 'enable_comment_counting' ), 10, 0 );
		add_action( 'action_scheduler_stored_action', array( $this, 'log_stored_action' ), 10, 1 );
		add_action( 'action_scheduler_canceled_action', array( $this, 'log_canceled_action' ), 10, 1 );
		add_action( 'action_scheduler_before_execute', array( $this, 'log_started_action' ), 10, 1 );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_completed_action' ), 10, 1 );
		add_action( 'action_scheduler_failed_execution', array( $this, 'log_failed_action' ), 10, 2 );
		add_action( 'action_scheduler_reset_action', array( $this, 'log_reset_action' ), 10, 1 );
		add_action( 'pre_get_comments', array( $this, 'filter_comment_queries' ), 10, 1 );
	}

	public function disable_comment_counting() {
		wp_defer_comment_counting(true);
	}
	public function enable_comment_counting() {
		wp_defer_comment_counting(false);
	}

	public function log_stored_action( $action_id ) {
		$this->log( $action_id, __('action created', 'action-scheduler') );
	}

	public function log_canceled_action( $action_id ) {
		$this->log( $action_id, __('action canceled', 'action-scheduler') );
	}

	public function log_started_action( $action_id ) {
		$this->log( $action_id, __('action started', 'action-scheduler') );
	}

	public function log_completed_action( $action_id ) {
		$this->log( $action_id, __('action complete', 'action-scheduler') );
	}

	public function log_failed_action( $action_id, Exception $exception ) {
		$this->log( $action_id, sprintf(__('action failed: %s', 'action-scheduler'), $exception->getMessage() ));
	}

	public function log_reset_action( $action_id ) {
		$this->log( $action_id, __('action reset', 'action_scheduler') );
	}

}