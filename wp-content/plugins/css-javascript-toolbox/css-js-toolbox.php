<?php
/*
Plugin Name: CSS & JavaScript Toolbox
Plugin URI: http://css-javascript-toolbox.com/
Description: Easily add CSS, JavaScript, HTML and PHP code to unique CJT code blocks and assign them anywhere on your website.
Version: 9.3
Author: Wipeout Media
Author URI: http://css-javascript-toolbox.com
License:

The Software is package as a WordPress¨ plugin.  The PHP code associated with the Software is licensed under the GPL version 2.0 license (as found at http://www.gnu.org/licenses/gpl-2.0.txt GNU/GPLv2 or "GPLv2"). You may redistribute, repackage, and modify the PHP code as you see fit and as consistent with GPLv2.

The remaining portions of the Software ("Proprietary Portion"), which includes all images, cascading style sheets, and JavaScript are NOT licensed under GPLv2 and are considered proprietary to Licensor and are solely licensed under the remaining terms of this Agreement.  The Proprietary Portion may not be redistributed, repackaged, or otherwise modified.
*/

// Disallow direct access.
defined( 'ABSPATH' ) or die( 'Access denied' );


/** * */
define( 'CJTOOLBOX_PLUGIN_BASE', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );

/** * */
define( 'CJTOOLBOX_PLUGIN_FILE', __FILE__ );

/** CJT Name */
define( 'CJTOOLBOX_NAME', plugin_basename( dirname( __FILE__ ) ) );

/** CJT Text Domain used for localize texts */
define( 'CJTOOLBOX_TEXT_DOMAIN', CJTOOLBOX_NAME );

/**  */
define( 'CJTOOLBOX_LANGUAGES', CJTOOLBOX_NAME . '/locals/languages/' );

/** CJT Absoulte path */
define( 'CJTOOLBOX_PATH', dirname( __FILE__ ) );

/** Dont use!! @deprecated */
define( 'CJTOOLBOX_INCLUDE_PATH', CJTOOLBOX_PATH . '/framework' );

/** Access Points  path */
define( 'CJTOOLBOX_ACCESS_POINTS', CJTOOLBOX_PATH . '/access.points' );

/** Frmaework path */
define('CJTOOLBOX_FRAMEWORK', CJTOOLBOX_INCLUDE_PATH); // Alias to include path!

// Class Autoload Added @since 6.2.
require 'autoload.inc.php';

// Import dependencies
require_once CJTOOLBOX_FRAMEWORK . '/php/includes.class.php';
require_once CJTOOLBOX_FRAMEWORK . '/events/definition.class.php';
require_once CJTOOLBOX_FRAMEWORK . '/events/events.class.php';
require_once CJTOOLBOX_FRAMEWORK . '/events/wordpress.class.php';
require_once CJTOOLBOX_FRAMEWORK . '/events/hookable.interface.php';
require_once CJTOOLBOX_FRAMEWORK . '/events/hookable.class.php';

// Initialize events engine/system!
CJTWordpressEvents::_init( array( 'hookType' => CJTWordpressEvents::HOOK_ACTION ) );
CJTWordpressEvents::$paths[ 'subjects' ][ 'core' ] = CJTOOLBOX_FRAMEWORK . '/events/subjects';
CJTWordpressEvents::$paths[ 'observers' ][ 'core' ] = CJTOOLBOX_FRAMEWORK . '/events/observers';

/**
* CJT Plugin interface.
*
* The CJT Plugin is maximum deferred.
* All functionality here is just to detect if the request should be processed!
*
* The main class is located css-js-toolbox.class.php cssJSToolbox class
* The plugin is fully developed using Model-View-Controller design pattern.
*
* access.points directory has all the entry points that processed by the Plugin.
*
* @package CJT
* @author Ahmed Said
* @version 6
*/
class CJTPlugin extends CJTHookableClass
{

	/**
	*
	*/
	const DB_VERSION = '1.6';

    /**
    * put your comment there...
    *
    * @var mixed
    */
    CONST ENV_PHP_MIN_VERSION = '5.3';

	/**
	*
	*/
	const FW_Version = '4.0';

	/**
	*
	*/
	const VERSION = '9.3';

	/**
	*
	*/
	const DB_VERSION_OPTION_NAME = 'cjtoolbox_db_version';

	/**
	*
	*/
	const PLUGIN_REQUEST_ID = 'cjtoolbox';

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $accessPoints;

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $extensions;

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $installed;

	/**
	* put your comment there...
	*
	* @var CJTPlugin
	*/
	protected static $instance;

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $mainAC;

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $onloaddbversion = array( 'parameters' => array( 'dbVersion' ) );

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $onimportcontroller = array( 'parameters' => array( 'file' ) );

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $onimportmodel  = array( 'parameters' => array( 'file' ) );

	/**
	* put your comment there...
	*
	* @var mixed
	*/
	protected $onload = array( 'parameters' => array( 'instance' ) );

	/**
	* put your comment there...
	*
	*/
	protected function __construct()
    {
		// Hookable!
		parent::__construct();

		// Allow access points to utilize from CJTPlugin functionality
		// even if the call is recursive inside getInstance/construct methods!!!
		self::$instance = $this;

		// Installation version
		$dbVersion = $this->onloaddbversion( get_option( self::DB_VERSION_OPTION_NAME ) );
		$this->installed = ( ( $dbVersion ) == self::DB_VERSION );

		// Load plugin and all installed extensions!.
		$this->load();
		$this->loadExtensions();

		// Run MAIN access point!
		$this->main();
	}

	/**
	* put your comment there...
	*
	*/
	public function & extensions()
    {
		return $this->extensions;
	}

	/**
	* put your comment there...
	*
	*/
	public function getAccessPoint( $name )
    {
		return $this->accessPoints[ $name ];
	}

	/**
	* put your comment there...
	*
	*/
	public static function getInstance()
    {

		if ( ! self::$instance )
        {
			self::$instance = new CJTPlugin();
		}

		return self::$instance;
	}

    /**
    * put your comment there...
    *
    */
    public static function isCompatibleEnvironment()
    {

        if ( version_compare( PHP_VERSION, self::ENV_PHP_MIN_VERSION, '<' ) )
        {

            $importHTMLFileCode = 'require "includes" . DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR . "incompatible_environment_message.html.php";';

            add_action( 'admin_notices', create_function( '', $importHTMLFileCode ) );

            return false;
        }

        return true;
    }
	/**
	* put your comment there...
	*
	*/
	public function isInstalled()
    {
		return $this->installed;
	}

	/**
	* put your comment there...
	*
	*/
	public function listen()
    {
		// For now we've only admin access points! Future versions might has something changed!
		if ( is_admin() )
        {

			// Import access points core classes.
			require_once 'framework/access-points/page.class.php';
			require_once 'framework/access-points/directory-spider.class.php';

			// Get access points!
			$accessPoints = CJTAccessPointsDirectorySpider::getInstance( 'CJT', CJTOOLBOX_ACCESS_POINTS );

			// For every access point create instance and LISTEN to the request!
			foreach ( $accessPoints as $name => $info )
            {
				/**
				* @var CJTAccessPoint
				*/
				$this->accessPoints[ $name ] = $point = $accessPoints->point()->listen();

				// We need to do some work with there is a connected access point.
				$point->onconnected = array( & $this, 'onconnected' );

			}

		}

		return $this;
	}

	/**
	* put your comment there...
	*
	*/
	protected function load()
    {
		// Bootstrap the Plugin!
		cssJSToolbox::getInstance();

		// Load MVC framework core!
		require_once $this->onimportmodel( CJTOOLBOX_MVC_FRAMEWOK . '/model.inc.php' );
		require_once $this->onimportcontroller( CJTOOLBOX_MVC_FRAMEWOK . '/controller.inc.php' );

		return $this;
	}

	/**
	* put your comment there...
	*
	*/
	protected function loadExtensions()
    {
		// Load extensions lib!
		require_once 'framework/extensions/extensions.class.php';

		$this->extensions = new CJTExtensions();
		$this->extensions->load();

		return $this;
	}

	/**
	* Run MAIN access point!
	*
	* @return $this
	*/
	protected function main()
    {
		// Fire laod event
		$this->onload( $this );

		// Access point base class is a dependency!
		require_once 'framework/access-points/access-point.class.php';

		// Run Main Acces Point!
		include_once 'access.points/main.accesspoint.php';

		$this->mainAC = new CJTMainAccessPoint();
		$this->mainAC->listen();
	}

	/**
	* Called When any In-Listen-State (ILS) Access point is
	* connected (called by Wordpress hooking system).
	*
	* @return boolean TRUE.
	*/
	public function onconnected( $observer, $state )
	{

		// In all cases that we'll process the request load the localization file.
		load_plugin_textdomain( CJTOOLBOX_TEXT_DOMAIN, false, CJTOOLBOX_LANGUAGES );

		do_action( CJTPluggableHelper::ACTION_CJT_TEXT_DOMAIN_LOADED );

		// Always connet  the access point!
		return $state;

	}

}// End Class

// Dont run if environment (e.g PHP version) is incomaptible
if ( ! CJTPlugin::isCompatibleEnvironment() )
{
    return;
}

// Initialize events!
CJTPlugin::define( 'CJTPlugin', array( 'hookType' => CJTWordpressEvents::HOOK_FILTER ) );

// Let's Go!
CJTPlugin::getInstance();