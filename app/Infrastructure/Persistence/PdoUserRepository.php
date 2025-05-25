<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(mixed $id): ?User
    {
        $query = 'SELECT * FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }

    public function findByUsername(string $username): ?User
    {
        $query     = 'SELECT * FROM users WHERE username = :u';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['u' => $username]);
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        if (! $data) {
            return null;
        }

        return new User(
            (int) $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }

    public function save(User $user): void
    {
        // TODO: Implement save() method.
         if ($user->getId() === null) {

            $query = 'INSERT INTO users (username, password_hash, created_at) VALUES (:u, :p, :c)';
            $stmt  = $this->pdo->prepare($query);
            $stmt->execute([
                'u' => $user->getUsername(),
                'p' => $user->getPasswordHash(),
                'c' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);

            $reflection = new \ReflectionClass($user);
            $prop = $reflection->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($user, (int) $this->pdo->lastInsertId());
        } else {

            $query = 'UPDATE users SET username = :u, password_hash = :p WHERE id = :id';
            $stmt  = $this->pdo->prepare($query);
            $stmt->execute([
                'u'  => $user->getUsername(),
                'p'  => $user->getPasswordHash(),
                'id' => $user->getId(),
            ]);
        }
    }
}
