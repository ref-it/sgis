<?php

namespace SimpleSAML\Module\sgis\Auth\Source;
use SimpleSAML\SessionHandler;

/**
 * Authentication source which let the user chooses among a list of
 * other authentication sources
 * SGIS: store selected source in a persistent cookie
 *
 * @author Lorenzo Gil, Yaco Sistemas S.L.
 * @package SimpleSAMLphp
 */

class MultiAuth extends \SimpleSAML\Auth\Source
{
    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = '\SimpleSAML\Module\sgis\Auth\Source\MultiAuth.AuthId';

    /**
     * The string used to identify our states.
     */
    const STAGEID = '\SimpleSAML\Module\sgis\Auth\Source\MultiAuth.StageId';

    /**
     * The key where the sources is saved in the state.
     */
    const SOURCESID = '\SimpleSAML\Module\sgis\Auth\Source\MultiAuth.SourceId';

    /**
     * The key where the selected source is saved in the session.
     */
    const SESSION_SOURCE = 'sgis:selectedSource';

    /**
     * Array of sources we let the user chooses among.
     */
    private $sources;

    /**
     * @var string|null preselect source in filter module configuration
     */
    private $preselect;

    /**
     * Storage for authsource config option remember.source.enabled
     * selectsource.php pages/templates use this option to present users with a checkbox
     * to save their choice for the next login request.
     * @var bool
     */
    protected $rememberSourceEnabled = FALSE;

    /**
     * Storage for authsource config option remember.source.checked
     * selectsource.php pages/templates use this option
     * to default the remember source checkbox to checked or not.
     * @var bool
     */
    protected $rememberSourceChecked = FALSE;

    protected $authproc = FALSE;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration.
     */
    public function __construct($info, $config)
    {
        assert(is_array($info));
        assert(is_array($config));

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        if (!array_key_exists('sources', $config)) {
            throw new \Exception('The required "sources" config option was not found');
        }

        if (array_key_exists('preselect', $config) && is_string($config['preselect'])) {
            if (!array_key_exists($config['preselect'], $config['sources'])) {
                throw new \Exception('The optional "preselect" config option must be present in "sources"');
            }

            $this->preselect = $config['preselect'];
        }

        $globalConfiguration = \SimpleSAML\Configuration::getInstance();
        $defaultLanguage = $globalConfiguration->getString('language.default', 'en');
        $authsources = \SimpleSAML\Configuration::getConfig('authsources.php');
        $this->sources = [];
        foreach ($config['sources'] as $source => $info) {
            if (is_int($source)) {
                // Backwards compatibility
                $source = $info;
                $info = [];
            }

            if (array_key_exists('text', $info)) {
                $text = $info['text'];
            } else {
                $text = [$defaultLanguage => $source];
            }

            if (array_key_exists('help', $info)) {
                $help = $info['help'];
            } else {
                $help = null;
            }
            if (array_key_exists('css-class', $info)) {
                $css_class = $info['css-class'];
            } else {
                // Use the authtype as the css class
                $authconfig = $authsources->getArray($source, null);
                if (!array_key_exists(0, $authconfig) || !is_string($authconfig[0])) {
                    $css_class = "";
                } else {
                    $css_class = str_replace(":", "-", $authconfig[0]);
                }
            }

            $this->sources[] = [
                'source' => $source,
                'text' => $text,
                'help' => $help,
                'css_class' => $css_class,
            ];
        }

        // Get the remember source config options
        if (isset($config['remember.source.enabled'])) {
        	$this->rememberSourceEnabled = (string) $config['remember.source.enabled'];
        }
        if (isset($config['remember.source.checked'])) {
        	$this->rememberSourceChecked = (bool) $config['remember.source.checked'];
        }
        /* authproc in wayfinder seems to be broken with simplesaml 1.17.7, so hack it here */
        if (isset($config['authproc'])) {
        	$this->authproc = $config['authproc'];
        }

    }

    /**
     * Getter for the authsource config option remember.source.enabled
     * @return bool
     */
    public function getRememberSourceEnabled() {
        return $this->rememberSourceEnabled;
    }

    /**
     * Getter for the authsource config option remember.source.checked
     * @return bool
     */
    public function getRememberSourceChecked() {
        return $this->rememberSourceChecked;
    }

    /**
     * Prompt the user with a list of authentication sources.
     *
     * This method saves the information about the configured sources,
     * and redirects to a page where the user must select one of these
     * authentication sources.
     *
     * This method never return. The authentication process is finished
     * in the delegateAuthentication method.
     *
     * @param array &$state Information about the current authentication.
     */
    public function authenticate(&$state)
    {
        assert(is_array($state));

        $state[self::AUTHID] = $this->authId;
        $state[self::SOURCESID] = $this->sources;

        if (!\array_key_exists('multiauth:preselect', $state) && is_string($this->preselect)) {
            $state['multiauth:preselect'] = $this->preselect;
        }

        // Save the $state array, so that we can restore if after a redirect
        $id = \SimpleSAML\Auth\State::saveState($state, self::STAGEID);

        /* Redirect to the select source page. We include the identifier of the
         * saved state array as a parameter to the login form
         */
        $url = \SimpleSAML\Module::getModuleURL('sgis/selectsource.php');
        $params = ['AuthState' => $id];

        // Allows the user to specify the auth source to be used
        if (isset($_GET['source'])) {
            $params['source'] = $_GET['source'];
        }

        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, $params);

        // The previous function never returns, so this code is never executed
        assert(false);
    }

    /**
     * Delegate authentication.
     *
     * This method is called once the user has choosen one authentication
     * source. It saves the selected authentication source in the session
     * to be able to logout properly. Then it calls the authenticate method
     * on such selected authentication source.
     *
     * @param string $authId Selected authentication source
     * @param array $state Information about the current authentication.
     */
    public static function delegateAuthentication($authId, $state)
    {
        assert(is_string($authId));
        assert(is_array($state));

        $as = \SimpleSAML\Auth\Source::getById($authId);
        $valid_sources = array_map(
            function ($src) {
                return $src['source'];
            },
            $state[self::SOURCESID]
        );
        if ($as === null || !in_array($authId, $valid_sources, true)) {
            throw new \Exception('Invalid authentication source: '.$authId);
        }

        // Save the selected authentication source for the logout process.
        $session = \SimpleSAML\Session::getSessionFromRequest();
        $session->setData(
            self::SESSION_SOURCE,
            $state[self::AUTHID],
            $authId,
            \SimpleSAML\Session::DATA_TIMEOUT_SESSION_END
        );

        // Save the state so we can to postprocessing
        $state['sgis:AuthID'] = $state[self::AUTHID];
        $stateId = \SimpleSAML\Auth\State::saveState($state, self::STAGEID);
        $state = [ "LoginCompletedHandler" => [ "\SimpleSAML\Module\sgis\Auth\Source\MultiAuth", "resumeAfterLogin" ],
                   "oldStateId" => $stateId ];
        try {
            $as->authenticate($state);
        } catch (\SimpleSAML\Error\Exception $e) {
            \SimpleSAML\Auth\State::throwException($state, $e);
        } catch (\Exception $e) {
            $e = new \SimpleSAML\Error\UnserializableException($e);
            \SimpleSAML\Auth\State::throwException($state, $e);
        }
        \SimpleSAML\Auth\Source::completeAuth($state);
    }

    public static function resumeAfterLogin(array $loginState) {
        assert(isset($loginState["oldStateId"]));

        $stateId = $loginState["oldStateId"];
        $state = \SimpleSaml\Auth\State::loadState($stateId, self::STAGEID);

        /*
         * Now we have the $state-array, and can use it to locate the authentication
         * source.
         */
        $source = \SimpleSAML\Auth\Source::getById($state['sgis:AuthID']);
        if ($source === null) {
            /*
             * The only way this should fail is if we remove or rename the authentication source
             * while the user is at the login page.
             */
            throw new \SimpleSAML\Error\Exception('Could not find authentication source with id ' . $state["sgis:AuthID"]);
        }

        /*
         * Make sure that we haven't switched the source type while the
         * user was at the authentication page. This can only happen if we
         * change config/authsources.php while an user is logging in.
         */
        if (!($source instanceof self)) {
            throw new \SimpleSAML\Error\Exception('Authentication source type changed.');
        }

        /*
         * OK, now we know that our current state is sane. Time to actually log the user in.
         *
         * First we check that the user is acutally logged in, and didn't simply skip the login page.
         */
        $attributes = $loginState["Attributes"];
        if ($attributes === null) {
            /*
             * The user isn't authenticated.
             *
             * Here we simply throw an exception, but we could also redirect the user back to the
             * login page.
             */
            throw new \SimpleSAML\Error\Exception('User not authenticated after login page.');
        }

        /*
         * So, we have a valid user. Time to run authproc
         */
        $state['Attributes'] = $attributes;

        if ($source->authproc) {

            $pc = new \SimpleSAML\Auth\ProcessingChain(["authproc" => $source->authproc], [], "multiauth");
            $state['ReturnCall'] = ['\SimpleSAML\Auth\Source', 'completeAuth'];
            $state['Destination'] = [];
            $state['Source'] = [];

            $pc->processState($state);
        }

        /*Time to resume the authentication process where we
         * paused it in the authenticate()-function above.
         */
        \SimpleSAML\Auth\Source::completeAuth($state);

        /*
         * The completeAuth-function never returns, so we never get this far.
         */
        assert(false);
    }

    /**
     * Log out from this authentication source.
     *
     * This method retrieves the authentication source used for this
     * session and then call the logout method on it.
     *
     * @param array &$state Information about the current logout operation.
     */
    public function logout(&$state)
    {
        assert(is_array($state));

        if ($this->rememberSourceEnabled) {
            $sessionHandler = SessionHandler::getSessionHandler();
            $params = $sessionHandler->getCookieParams();
            $params['expire'] = time();
            $params['expire'] += -300;
            setcookie($this->getAuthId() . '-source', "", $params['expire'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        // Get the source that was used to authenticate
        $session = \SimpleSAML\Session::getSessionFromRequest();
        $authId = $session->getData(self::SESSION_SOURCE, $this->authId);

        if ($authId === NULL) /* maybe session expired */
            return;

        $source = \SimpleSAML\Auth\Source::getById($authId);
        if ($source === null) {
            throw new \Exception('Invalid authentication source during logout: '.$source);
        }
        // Then, do the logout on it
        $source->logout($state);
    }

    /**
     * Set the previous authentication source.
     *
     * This method remembers the authentication source that the user selected
     * by storing its name in a cookie.
     *
     * @param string $source Name of the authentication source the user selected.
     */
    public function setPreviousSource($source)
    {
        assert(is_string($source));

        $cookieName = 'sgis_source_'.$this->authId;

        $config = \SimpleSAML\Configuration::getInstance();
        $params = [
            // We save the cookies for 90 days
            'lifetime' => 7776000, //60*60*24*90
            // The base path for cookies. This should be the installation directory for SimpleSAMLphp.
            'path' => $config->getBasePath(),
            'httponly' => false,
        ];

        \SimpleSAML\Utils\HTTP::setCookie($cookieName, $source, $params, false);
    }

    /**
     * Get the previous authentication source.
     *
     * This method retrieves the authentication source that the user selected
     * last time or NULL if this is the first time or remembering is disabled.
     */
    public function getPreviousSource()
    {
        $cookieName = 'sgis_source_'.$this->authId;
        if (array_key_exists($cookieName, $_COOKIE)) {
            return $_COOKIE[$cookieName];
        } else {
            return null;
        }
    }

    public function getConfig() {
        $config = parent::getConfig();

var_dump($config);die();

        return $config;
    }
}
