<?php


class BWQM_Dispatcher_Html extends BWQM_Dispatcher {

	public $id = 'html';

	public function __construct( BWQM_Plugin $bwqm ) {

		add_action( 'admin_bar_menu',             array( $this, 'action_admin_bar_menu' ), 999 );
		add_action( 'wp_ajax_bwqm_auth_on',         array( $this, 'ajax_on' ) );
		add_action( 'wp_ajax_bwqm_auth_off',        array( $this, 'ajax_off' ) );
		add_action( 'wp_ajax_nopriv_bwqm_auth_off', array( $this, 'ajax_off' ) );

		parent::__construct( $bwqm );

	}

	/**
	 * Helper function. Should the authentication cookie be secure?
	 *
	 * @return bool Should the authentication cookie be secure?
	 */
	public static function secure_cookie() {
		return ( is_ssl() and ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) ) );
	}

	public function ajax_on() {

		if ( ! current_user_can( 'can_bitwize' ) or ! check_ajax_referer( 'bwqm-auth-on', 'nonce', false ) ) {
			wp_send_json_error( __( 'Could not set authentication cookie.', BW_TD ) );
		}

		$expiration = time() + 172800; # 48 hours
		$secure     = self::secure_cookie();
		$cookie     = wp_generate_auth_cookie( get_current_user_id(), $expiration, 'logged_in' );

		setcookie( BWQM_COOKIE, $cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure, false );

		$text = __( 'Authentication cookie set. You can now view Query Monitor output while logged out or while logged in as a different user.', BW_TD );

		wp_send_json_success( $text );

	}

	public function ajax_off() {

		if ( ! $this->user_verified() or ! check_ajax_referer( 'bwqm-auth-off', 'nonce', false ) ) {
			wp_send_json_error( __( 'Could not clear authentication cookie.', BW_TD ) );
		}

		$expiration = time() - 31536000;

		setcookie( BWQM_COOKIE, ' ', $expiration, COOKIEPATH, COOKIE_DOMAIN );

		$text = __( 'Authentication cookie cleared.', BW_TD );

		wp_send_json_success( $text );

	}

	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( ! $this->user_can_view() ) {
			return;
		}

		$class = implode( ' ', array( 'hide-if-js' ) );
		$title = __( 'Query Monitor', BW_TD );

		$wp_admin_bar->add_menu( array(
			'id'    => 'query-monitor',
			'title' => $title,
			'href'  => '#bwqm-overview',
			'meta'  => array(
				'classname' => $class
			)
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
			'title'  => $title,
			'href'   => '#bwqm-overview'
		) );

	}

	public function init() {

		if ( ! $this->user_can_view() ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', 1 );
		}

		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );

	}

	public function enqueue_assets() {

		global $wp_locale, $wp_version;

		wp_enqueue_style(
			'query-monitor',
			$this->bwqm->plugin_url( 'assets/query-monitor.css' ),
			null,
			$this->bwqm->plugin_ver( 'assets/query-monitor.css' )
		);
		wp_enqueue_script(
			'query-monitor',
			$this->bwqm->plugin_url( 'assets/query-monitor.js' ),
			array( 'jquery' ),
			$this->bwqm->plugin_ver( 'assets/query-monitor.js' ),
			true
		);
		wp_localize_script(
			'query-monitor',
			'bwqm_locale',
			(array) $wp_locale
		);
		wp_localize_script(
			'query-monitor',
			'bwqm_l10n',
			array(
				'ajax_error'            => __( 'PHP Error in AJAX Response', BW_TD ),
				'infinitescroll_paused' => __( 'Infinite Scroll has been paused by Query Monitor', BW_TD ),
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'auth_nonce' => array(
					'on'         => wp_create_nonce( 'bwqm-auth-on' ),
					'off'        => wp_create_nonce( 'bwqm-auth-off' ),
				),
			)
		);

		if ( floatval( $wp_version ) <= 3.7 ) {
			wp_enqueue_style(
				'query-monitor-compat',
				$this->bwqm->plugin_url( 'assets/compat.css' ),
				null,
				$this->bwqm->plugin_ver( 'assets/compat.css' )
			);
		}

	}

	public function before_output() {

		require_once $this->bwqm->plugin_path( 'output/Html.php' );

		BWQM_Util::include_files( $this->bwqm->plugin_path( 'output/html' ) );

		$class = array(
			'bwqm-no-js',
		);

		if ( !is_admin() ) {
			$absolute = function_exists( 'twentyfifteen_setup' );
			if ( apply_filters( 'bwqm/output/absolute_position', $absolute ) ) {
				$class[] = 'bwqm-absolute';
			}
		}

		if ( !is_admin_bar_showing() ) {
			$class[] = 'bwqm-show';
		}

		echo '<div id="bwqm" class="' . implode( ' ', $class ) . '">';
		echo '<div id="bwqm-wrapper">';
		echo '<p>' . __( 'Query Monitor', BW_TD ) . '</p>';

	}

	public function after_output() {

		echo '<div class="bwqm bwqm-half" id="bwqm-authentication">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Authentication', BW_TD ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( !$this->user_verified() ) {

			echo '<tr>';
			echo '<td>' . __( 'You can set an authentication cookie which allows you to view Query Monitor output when you&rsquo;re not logged in.', BW_TD ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" class="bwqm-auth" data-action="on">' . __( 'Set authentication cookie', BW_TD ) . '</a></td>';
			echo '</tr>';

		} else {

			echo '<tr>';
			echo '<td>' . __( 'You currently have an authentication cookie which allows you to view Query Monitor output.', BW_TD ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" class="bwqm-auth" data-action="off">' . __( 'Clear authentication cookie', BW_TD ) . '</a></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '</div>';
		echo '</div>';

		$json = array(
			'menu'        => $this->js_admin_bar_menu(),
			'ajax_errors' => array() # @TODO move this into the php_errors collector
		);

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $json ) . ';' . "\n\n";
		?>
		if ( 'undefined' === typeof BWQM_i18n ) {
			document.getElementById( 'qm' ).style.display = 'block';
		}
		<?php
		echo '</script>' . "\n\n";

	}

	public function js_admin_bar_menu() {

		$class = implode( ' ', apply_filters( 'bwqm/output/menu_class', array() ) );
		$title = implode( '&nbsp;&nbsp;&nbsp;', apply_filters( 'bwqm/output/title', array() ) );

		if ( empty( $title ) ) {
			$title = __( 'Query Monitor', BW_TD );
		}

		$admin_bar_menu = array(
			'top' => array(
				'title'     => sprintf( '<span class="ab-icon">QM</span><span class="ab-label">%s</span>', $title ),
				'classname' => $class
			),
			'sub' => array()
		);

		foreach ( apply_filters( 'bwqm/output/menus', array() ) as $menu ) {
			$admin_bar_menu['sub'][] = $menu;
		}

		return $admin_bar_menu;

	}

	public function is_active() {

		if ( ! $this->user_can_view() ) {
			return false;
		}

		if ( ! ( did_action( 'wp_footer' ) or did_action( 'admin_footer' ) or did_action( 'login_footer' ) ) ) {
			return false;
		}

		if ( BWQM_Util::is_async() ) {
			return false;
		}

		return true;

	}

}

function register_bwqm_dispatcher_html( array $dispatchers, BWQM_Plugin $bwqm ) {
	$dispatchers['html'] = new BWQM_Dispatcher_Html( $bwqm );
	return $dispatchers;
}

add_filter( 'bwqm/dispatchers', 'register_bwqm_dispatcher_html', 10, 2 );
