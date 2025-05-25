<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {
        // TODO: check that a user with same username does not exist, create new user and persist
        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that

        // TODO: here is a sample code to start with

        if ($this->userExists($username)) {
            throw new \RuntimeException("Username '{$username}' already taken");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $user = new User(null, $username, $hash, new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): ?User
    {
        $user = $this->users->findByUsername($username);
        if (! $user) {
            return null;
        }
        if (password_verify($password, $user->getPasswordHash())) {
            return $user;
        }
        return null;
    }

    public function userExists(string $username): bool
    {
        return (bool) $this->users->findByUsername($username);
    }
}
