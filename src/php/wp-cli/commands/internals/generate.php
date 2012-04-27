<?php

WP_CLI::addCommand('generate', 'Generate_Command');

/**
 * Implement generate command
 *
 * @package wp-cli
 * @subpackage commands/internals
 */
class Generate_Command extends WP_CLI_Command {

	/**
	 * Generate posts
	 *
	 * @param array $args
	 * @param array $assoc_args
	 **/
	public function posts( $args, $assoc_args ) {
		global $wpdb;

		$defaults = array(
			'count' => 100,
			'type' => 'post',
			'status' => 'publish',
			'author' => false
		);

		extract( wp_parse_args( $assoc_args, $defaults ), EXTR_SKIP );

		if ( !post_type_exists( $type ) ) {
			WP_CLI::warning( 'invalid post type.' );
			exit;
		}

		if ( $author ) {
			$author = get_user_by( 'login', $author );

			if ( $author )
				$author = $author->ID;
		}

		// Get the total number of posts
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s", $type ) );

		$label = get_post_type_object( $type )->labels->singular_name;

		$limit = $count + $total;

		$notify = new \cli\progress\Bar( 'Generating posts', $count );

		for ( $i = $total; $i < $limit; $i++ ) {
			wp_insert_post( array(
				'post_type' => $type,
				'post_title' =>  "$label $i",
				'post_status' => $status,
				'post_author' => $author
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Generate users
	 *
	 * @param array $args
	 * @param array $assoc_args
	 **/
	public function users( $args, $assoc_args ) {
		global $blog_id;

		$defaults = array(
			'count' => 100,
			'role' => get_option('default_role'),
		);

		extract( wp_parse_args( $assoc_args, $defaults ), EXTR_SKIP );

		if ( 'none' == $role ) {
			$role = false;
		} elseif ( is_null( get_role( $role ) ) ) {
			WP_CLI::warning( "invalid role." );
			exit;
		}

		$user_count = count_users();

		$total = $user_count['total_users'];

		$limit = $count + $total;

		$notify = new \cli\progress\Bar( 'Generating users', $count );

		for ( $i = $total; $i < $limit; $i++ ) {
			$login = sprintf( 'user_%d_%d', $blog_id, $i );
			$name = "User $i";

			$user_id = wp_insert_user( array(
				'user_login' => $login,
				'user_pass' => $login,
				'nickname' => $name,
				'display_name' => $name,
				'role' => $role
			) );

			if ( false === $role ) {
				delete_user_option( $user_id, 'capabilities' );
				delete_user_option( $user_id, 'user_level' );
			}

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Help function for this command
	 */
	public static function help() {
		WP_CLI::line( <<<EOB
usage: wp generate posts [--count=100] [--type=post] [--status=publish] [--author=<login>]
   or: wp generate users [--count=100] [--role=<role>]
EOB
	);
	}
}
