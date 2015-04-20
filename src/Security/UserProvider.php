<?php
namespace Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\DBAL\Connection;

 
class UserProvider implements UserProviderInterface
{
    private $conn;
 
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
 
    public function loadUserByUsername($username)
    {
        $stmt = $this->conn->executeQuery('SELECT * FROM usuarios WHERE user = ?', array(strtolower($username)));
 
        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf('El usuario "%s" no existe.', $username));
        }
 
        return new User($user['user'], $user['pass'], explode(',', $user['puesto']), true, true, true, true);
    }
 
    public function refreshUser(UserInterface $user)
    { 
        return $this->loadUserByUsername($user->getUsername());
    }
 
    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}