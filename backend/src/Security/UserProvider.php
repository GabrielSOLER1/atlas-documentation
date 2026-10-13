<?php

namespace App\Security;

use App\Service\Database;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(private Database $database) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Check activation whenever the session is refreshed.
        $query = $this->database->connection()->prepare('SELECT id, email, password, name, role, department, job_title, bio, reading_size, accent, avatar_version, avatar IS NOT NULL AS has_avatar FROM users WHERE lower(email) = :email AND active = TRUE');
        $query->execute(['email' => strtolower(trim($identifier))]);
        $row = $query->fetch();
        if (!$row) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);
            throw $exception;
        }
        return new User($row['email'], $row['password'], $row['name'], $row['role'], (int) $row['id'], $row);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool { return $class === User::class; }
}
