<?php
/**
 * chophel@athang.com @2021
 */
namespace Auth\Storage;
use Laminas\Authentication\Storage\Session;
use Laminas\Authentication\Storage\StorageInterface;

class AuthStorage implements StorageInterface
{
    const NAME = 'nangtenSession';
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var mixed
     */
    private $resolvedIdentity;
    /**
     * Returns true if and only if storage is empty
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If it is impossible to determine whether storage is empty
     * @return bool
     */
    public function isEmpty()
    {
        if ($this->getStorage()->isEmpty()) {
            return true;
        }
        $identity = $this->getStorage()->read();
        if ($identity === null) {
            $this->clear();
            return true;
        }
        return false;
    }
    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If reading contents from storage is impossible
     * @return mixed
     */
    public function read()
    {
        if ($this->resolvedIdentity !== null) {
            return $this->resolvedIdentity;
        }
        $identity = $this->getStorage()->read();
        if ($identity) {
            $this->resolvedIdentity = $identity;
        } else {
            $this->resolvedIdentity = null;
        }
        return $this->resolvedIdentity;
    }
    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If writing $contents to storage is impossible
     * @return void
     */
    public function write($contents)
    {
        $this->resolvedIdentity = null;
        $this->getStorage()->write($contents);
    }
    /**
     * Clears contents from storage
     *
     * @throws \Laminas\Authentication\Exception\ExceptionInterface If clearing contents from storage is impossible
     * @return void
     */
    public function clear()
    {
        $this->resolvedIdentity = null;
        $this->getStorage()->clear();
    }
    /**
     * @param StorageInterface $storage
     *
     * @return AuthStorage $this
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
        return $this;
    }
    /**
     * @return StorageInterface
     */
    public function getStorage()
    {
        if ($this->storage === null) {
            $this->setStorage(new Session(self::NAME));
        }
        return $this->storage;
    }
}
