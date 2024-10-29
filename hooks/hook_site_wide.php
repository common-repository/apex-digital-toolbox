<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}
// Ensure there are no conflicts
if ( ! class_exists( 'toolboxHookSiteWide' ) ) {
	class toolboxHookSiteWide extends toolboxHookController {
		private $_production = true;

		function __construct( $Toolbox ) {
			parent::__construct( $Toolbox );
			$this->setLabel( 'Site Wide' );
			$this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'admin_init', 'addSettings', Array(
				'label'       => 'Production/Staging',
				'description' => 'Allows the ability to define the production URL for the installation. Once set the site can offer restrictions via IP or cookie to the staging site.'
			) );
			$this->addHook( APEX_TOOLBOX_HOOK_ACTION, 'init', 'isStagingAccessAuthorized', Array(
				'label'       => 'Website Restriction',
				'description' => 'If a site is in staging mode then a 401 will be returned unless the IP matches an authorized one or the cookie is in place.'
			) );
		}

		/**
		 * Add production url setting
		 *
		 * @param array $args Any arguments passed to the callback
		 *
		 * @author Nigel Wells
		 * @version 0.3.4.16.10.31
		 * @return void;
		 */
		public function addSettings( $args = Array() ) {
			// Setup the new field details
			$description = 'Used to identify if this installation matches the main production URL. Allows for other triggers when viewing staging site compared to production sites.<br />
					Current installation is on: <strong>' . ( $this->onProduction() ? 'PRODUCTION' : 'STAGING' ) . '</strong>.';
			// Show URL to allow cookie access for staging sites
			if ( ! $this->onProduction() && $this->Toolbox->isHookEnabled( 'site_wide', 'isStagingAccessAuthorized' ) ) {
				$description .= '<br />Cookie access to the site: ' . $this->getStagingCookieURL();
			}
			$this->Toolbox->addSetting( Array(
				'name'        => 'production_url',
				'label'       => 'Production URL',
				'type'        => 'url',
				'value'       => $this->Toolbox->getOption( 'production_url' ),
				'description' => $description
			), $this->getLabel() );
			if ( $this->Toolbox->isHookEnabled( 'site_wide', 'isStagingAccessAuthorized' ) ) {
				$allowed_staging_ip = $this->Toolbox->getOption( 'allowed_staging_ip' );
				// Default to current IP if nothing has been set
				if ( ! $allowed_staging_ip && isset( $_SERVER['REMOTE_ADDR'] ) ) {
					$allowed_staging_ip = $_SERVER['REMOTE_ADDR'];
					$this->sendFirstTimeStagingEmailSetup( $allowed_staging_ip );
				}
				$this->Toolbox->addSetting( Array(
					'name'        => 'allowed_staging_ip',
					'label'       => 'Allowed Staging IPs',
					'type'        => 'text',
					'value'       => $allowed_staging_ip,
					'description' => 'Enter the IP addresses (comma separated) that are allowed access to the staging site without needing the cookie authenticated.'
				), $this->getLabel() );
			}
		}

		/**
		 * Checks to see if this installation is running on the production URL or if it is on a staging site
		 *
		 * @author Nigel Wells
		 * @version 0.02.1.16.10.07
		 * @return void;
		 */
		private function checkIfProduction() {
			if ( ! $this->Toolbox->getOption( 'production_url' ) || ! $this->Toolbox->isHookEnabled( 'site_wide', 'addSettings' ) ) {
				$production = true;
			} else {
				// Check if in staging or production
				if ( getDomain( get_site_url() ) == getDomain( $this->Toolbox->getOption( 'production_url' ) ) ) {
					$production = true;
				} else {
					$production = false;
				}
			}
			$this->_production = $production;
		}

		/**
		 * Is the site being viewed on the production server or not
		 *
		 * @author Nigel Wells
		 * @version 0.2.1.16.10.07
		 * @return boolean
		 */
		function onProduction() {
			$this->checkIfProduction();

			return $this->_production;
		}

		/**
		 * Checks to see if this access is permitted to the staging installation
		 *
		 * @author Nigel Wells
		 * @return void
		 */
		public function isStagingAccessAuthorized( $args = Array() ) {
			// Run logic to see if on staging or production
			$this->checkIfProduction();
			// If not on the production site then only allow users from set IP addresses otherwise return a 404
			if ( ! $this->onProduction() ) {
				$authorized = false;
				// If logged on then let them in
				if ( is_user_logged_in() ) {
					$authorized = true;
				}
				// If this is DDEV development platform then it is fine
        if ( !empty($_SERVER['DDEV_PROJECT_TYPE']) && $_SERVER['DDEV_PROJECT_TYPE'] === 'wordpress') {
          $authorized = true;
        }
				// Check if on the main login screen
				if ( ! $authorized && $GLOBALS['pagenow'] === 'wp-login.php' ) {
					$authorized = true;
				}
				// Check if cron task is being run
				if ( ! $authorized && $GLOBALS['pagenow'] === 'wp-cron.php' ) {
					$authorized = true;
				}
				// Check if in the admin area
				if ( ! $authorized && is_admin() ) {
					$authorized = true;
				}
				// Get allowed IP from settings - this is where clients IP will go
				if ( ( $this->Toolbox->getOption( 'allowed_staging_ip' ) ) ) {
					$allowed = explode( ',', $this->Toolbox->getOption( 'allowed_staging_ip' ) );
					// Trim them up in case spaces were entered
					$allowed = array_map( 'trim', $allowed );
				} else {
					$allowed = Array();
				}
				// Loop through allowed IPs to see if authorized or not
				if ( ! $authorized && isset( $_SERVER['REMOTE_ADDR'] ) ) {
					foreach ( $allowed as $ip ) {
						if ( strpos( $_SERVER['REMOTE_ADDR'], $ip ) !== false ) {
							$authorized = true;
							break;
						}
					}
				}
				// Check if cookie setup to authorize
				if ( ! $authorized && isset( $_COOKIE['apex_toolbox_staging'] ) ) {
					if ( $_COOKIE['apex_toolbox_staging'] == $this->getStagingCookie() ) {
						$authorized = true;
					}
				} elseif ( isset( $_GET['request'] ) && isset( $_GET['code'] ) && $_GET['request'] == 'staging' ) {
					// Check to see if this is valid and set as a cookie if it is
					if ( $_GET['code'] == md5( AUTH_KEY ) ) {
						setcookie( 'apex_toolbox_staging', $_GET['code'], strtotime( '+1 month' ) );
						// Redirect to the home page
						header( 'Location: /' );
						exit;
					}
				}
				// If not authorized, but no valid IPs added either, then let it through otherwise we'll be locked out of the site!
				if ( ! $authorized && ! count( $allowed ) ) {
					$authorized = true;
				}
				if ( ! $authorized ) {
					header( 'HTTP/1.1 401 Unauthorized' );
					// Display a message about not being authorized and also an easy way for clients to provide their IP address to us
					echo '<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<style>
body,h1,p {
	font-family: Verdana, Arial;
}
.face {
	font-size: 136px;
	font-family: "Times New Roman";
	font-weight: bold;
	margin: 0;
	padding: 0 0 10px;
}
</style>
</head>
<body>
<p class="face">:(</p>
<h1>You are unauthorized to view this website</h1>
<p>This site is in development mode and authorization is required to view it</p>
<p>If you feel this been a mistake please contact your website administrator for the process to get authorized</p>
' . ( isset( $_SERVER['REMOTE_ADDR'] ) ? '<p>Your IP is logged as: <b>' . $_SERVER['REMOTE_ADDR'] . '</b></p>' : '' ) . '
</body>
</html>';
					exit;
				}
			}
		}

		/**
		 * Sends out an email to the currently logged in user notifying them of the staging code as well as adding their IP to the allowed list
		 *
		 * @param string $allowed_staging_ip IP to set as allowed to view the site without needing the cookie code
		 *
		 * @author Nigel Wells
		 * @version 0.3.4.16.10.31
		 * @return void;
		 */
		private function sendFirstTimeStagingEmailSetup( $allowed_staging_ip ) {
			// Save the IP as an option
			$this->Toolbox->setOption( 'allowed_staging_ip', $allowed_staging_ip );
			// Send email out with the cookie address for safe keeping
			$current_user = wp_get_current_user();
			$to           = $current_user->user_email;
			$subject      = getDomain( get_bloginfo( 'url' ) ) . ': Cookie for staging access';
			$body         = 'Here is the staging cookie URL if you ever need to gain access to the site and your IP is not valid: ' . $this->getStagingCookieUrl() . '. Keep this email especially if you\'re running the site off a dynamic IP.';

			wp_mail( $to, $subject, $body );
		}

		/**
		 * Get the full URL that includes the cookie code - used to access the site when in staging mode
		 *
		 * @author Nigel Wells
		 * @version 0.3.4.16.10.31
		 * @return string;
		 */
		private function getStagingCookieUrl() {
			return get_site_url() . '?request=staging&code=' . $this->getStagingCookie();
		}

		/**
		 * Get the staging cookie code to be used when in staging mode
		 *
		 * @author Nigel Wells
		 * @version 0.3.4.16.10.31
		 * @return string;
		 */
		private function getStagingCookie() {
			return md5( AUTH_KEY );
		}
	}

}