<?php
namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\Container;
use Laminas\Session\ManagerInterface as Manager;
use Laminas\Stdlib\SplQueue;

/**
 * Session Array - implement session-based contents
 */
class SessionArrayPlugin extends AbstractPlugin
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * contents from previous request
     * @var array
     */
    protected $contents = array();

    /**
     * @var Manager
     */
    protected $session;

	/**
     * Whether a content has been added during this request
     *
     * @var bool
     */
    protected $contentAdded = false;

    /**
     * Set the session manager
     *
     * @param  Manager   $manager
     * @return SessionArray
     */
    public function setSessionManager(Manager $manager)
    {
        $this->session = $manager;

        return $this;
    }

    /**
     * Retrieve the session manager
     *
     * If none composed, lazy-loads a SessionManager instance
     *
     * @return Manager
     */
    public function getSessionManager()
    {
        if (!$this->session instanceof Manager) {
            $this->setSessionManager(Container::getDefaultManager());
        }

        return $this->session;
    }

    /**
     * Get session container for flash contents
     *
     * @return Container
     */
    public function getContainer()
    {
        if ($this->container instanceof Container) {
            return $this->container;
        }

        $manager = $this->getSessionManager();
        $this->container = new Container('Sessionarray', $manager);

        return $this->container;
    }
    
    /**
     * Whether a specific namespace has messages
     *
     * @return bool
     */
    public function hasContents($key)
    {
        $this->getContentsFromContainer();

        return isset($this->contents[$key]);
    }

    /**
     * Add a content
     *
     * @param  string         $content
     * @return FlashMessenger Provides a fluent interface
     */
    public function addContent($key,$content)
    {
        $container = $this->getContainer();

        if (!$this->contentAdded) {
            $this->getcontentsFromContainer();
            $container->setExpirationHops(1, null);
        }

        if (!isset($container->{$key})
            || !($container->{$key} instanceof SplQueue)
        ) {
            $container->{$key} = new SplQueue();
        }

        $container->{$key}->push($content);

        $this->contentAdded = true;

        return $this;
    }

    /**
     * Get contents from a specific key
     *
     * @return array
     */
    public function getContents($key)
    {
        if ($this->hasContents($key)) {
            return $this->contents[$key]->toArray();
        }

        return array();
    }

    /**
     * Clear all contents from the previous request & current key
     *
     * @return bool True if contents were cleared, false if none existed
     */
    public function clearContent($key)
    {
        if ($this->hasContents($key)) {
            unset($this->contents[$key]);

            return true;
        }

        return false;
    }
    
    /**
     * Pull contents from the session container
     *
     * Iterates through the session container, removing contents into the local
     * scope.
     *
     * @return void
     */
    protected function getContentsFromContainer()
    {
        if (!empty($this->content)) {
            return;
        }

        $container = $this->getContainer();

        $key = array();
        foreach ($container as $key => $contents) {
            $this->contents[$key] = $contents;
            $keys[] = $key;
        }
    }
}
