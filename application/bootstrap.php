<?php
define('VFSEARCH_BASE_PATH', dirname(__FILE__) . '/..');

class bootstrap
{

    static $_instance;
    protected $router;
    protected $frontController;

    /** @var Zend_Session */
    protected $session;

    /* @return bootstrap */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    function execute()
    {
        $this->setIncludePath();
        $this->setupAutoloading();

        $this->frontController = Zend_Controller_Front::getInstance();
        $this->addRoutes();
        $this->setupDatabaseConfig();
        $this->startDb();
        $this->setupSession();

        Zend_Controller_Front::getInstance()->setControllerDirectory(
            array(
                'default' => VFSEARCH_BASE_PATH . '/application/Default/controllers',
            )
        );
        Zend_Layout::startMvc(VFSEARCH_BASE_PATH . '/layout/');
        $this->setupViewHelperPaths();
        $this->getUser();
        return $this;
    }

    function addRoutes()
    {
        $route = new Zend_Controller_Router_Route(
            '/parts/:make/:model/:year/:item_name/:page',
            array(
                'controller' => 'Index',
                'action' => 'index',
                'item_name' => '',
                'make' => '',
                'model' => '',
                'year' => '',
                'page' => 1
            )
        );
        Zend_Controller_Front::getInstance()->getRouter()->addRoute('parts', $route);
        $route = new Zend_Controller_Router_Route(
            '/parts/:page',
            array(
                'controller' => 'Index',
                'action' => 'index',
                'page' => 1
            )
        );
        Zend_Controller_Front::getInstance()->getRouter()->addRoute('browse', $route);
    }

    function setupViewHelperPaths()
    {

    }

    /** @return Zend_Session */
    public function getSession()
    {
        return $this->session;
    }

    /** @return User */
    function getUser()
    {
        if (isset($this->session->user) && $this->session->user) {
            $this->user = $this->session->user;
            Zend_Registry::set('user', $this->user);
        } else {
            return false;
        }
        return $this->user;
    }

    function userLogout()
    {
        unset($this->getSession()->user);
    }

    function basePath()
    {
        return VFSEARCH_BASE_PATH;
    }

    function setupSession()
    {
        $this->session = new Zend_Session_Namespace('vfsearch');
    }

    function getRouter()
    {
        if (!is_null($this->router)) {
            return $this->router;
        }
        if (is_null($this->frontController)) {
            throw new Exception('No front controller');
        }
        $this->router = $this->frontController->getRouter();
        return $this->router;
    }

    function setIncludePath()
    {
        set_include_path(
            get_include_path() . PATH_SEPARATOR .
            'application/' . PATH_SEPARATOR .
            'Vehicle-Fits-Core/library/'
        );
    }

    function setupAutoloading()
    {
        require_once 'vendor/autoload.php';
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);
    }

    function setupDatabaseConfig()
    {
        $config = new Zend_Config_Ini(VFSEARCH_BASE_PATH . '/config/database-config.ini', 'localhost');
        Zend_Registry::set('database_config', $config);
        Zend_Registry::set('mysql_command', $config->mysql_command);
    }

    function startDb()
    {
        $configuration = Zend_Registry::get('database_config');
        Zend_Registry::set('db', new Zend_Db_Adapter_Pdo_Mysql($configuration->database->params));
    }

}

$bootstrap = bootstrap::getInstance();
$bootstrap->execute();