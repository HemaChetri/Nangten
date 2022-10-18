<?php
namespace Application\Controller\Plugin;
 
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Interop\Container\ContainerInterface;

class PasswordPlugin extends AbstractPlugin{

	private $_container;

    public function __construct(ContainerInterface $container)
    {
        $this->_container = $container;
    }
	public function generateDynamicSalt()
    {
        $dynamicSalt = '';
        for ($i = 0; $i < 20; $i++) {
            $dynamicSalt .= chr(rand(33, 126));
        }
        return $dynamicSalt;
    }
    
    public function generatePassword()
    {
        $password = chr(rand(65, 90)); /** upper case */
        $password .= chr(rand(97, 122)); /** lower case */
        $password .= chr(rand(48, 57)); /** 0 - 9 */
        $password .= chr(rand(48, 57)); /** 0 - 9 */
        $password .= chr(rand(48, 57)); /** 0 - 9 */ 
        $password .= chr(rand(65, 90)); /** upper case */
        $password .= chr(rand(97, 122)); /** lower case */
        $password .= chr(rand(35, 38)); /** # $ % & */

        $password = '1234qwer';
        return $password;
    }
    
    public function getStaticSalt()
    {
        $staticSalt = '';
        $config = $this->_container->get('Config');
        $staticSalt = $config['static_salt'];
        return $staticSalt;
    }
    
    public function encryptPassword($staticSalt, $password, $dynamicSalt)
    {
        return $password = SHA1($staticSalt . $password . $dynamicSalt);
    }
}	