<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $stmt = $this->pdo->prepare('SELECT * FROM expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->createExpenseFromData($row) : null;
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $stmt ->execute(['id' => $id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        $sql = "
        SELECT *
        FROM expenses
        WHERE user_id = :u
          AND date BETWEEN :from AND :to
        ORDER BY date DESC
        LIMIT :l OFFSET :o
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue('u', $criteria['user_id'], PDO::PARAM_INT);
    $stmt->bindValue('from', $criteria['date_from']);
    $stmt->bindValue('to',   $criteria['date_to']);
    $stmt->bindValue('l', $limit,  PDO::PARAM_INT);
    $stmt->bindValue('o', $from,   PDO::PARAM_INT); 
    $stmt->execute();

    $rows = $stmt->fetchAll();
    return array_map(fn($r) => $this->createExpenseFromData($r), $rows);
    }


    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.
         $sql = "
        SELECT COUNT(*) AS cnt
        FROM expenses
        WHERE user_id = :u
          AND date BETWEEN :from AND :to
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'u'    => $criteria['user_id'],
        'from' => $criteria['date_from'],
        'to'   => $criteria['date_to'],
    ]);

    return (int)$stmt->fetchColumn();
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
            $sql = "
      SELECT DISTINCT strftime('%Y', date) AS year
      FROM expenses
      WHERE user_id = :u
      ORDER BY year DESC
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['u' => $user->id]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        $sql = "
      SELECT category, SUM(amount_cents) AS total
      FROM expenses
      WHERE user_id = :u
        AND strftime('%Y', date) = :y
        AND strftime('%m', date) = :m
      GROUP BY category
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      'u' => $c['user_id'],
      'y' => str_pad((string)$c['year'], 4, '0', STR_PAD_LEFT),
      'm' => str_pad((string)$c['month'], 2, '0', STR_PAD_LEFT),
    ]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['category']] = (int)$row['total'];
    }
    return $result;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        $sql = "
      SELECT category, AVG(amount_cents) AS avg
      FROM expenses
      WHERE user_id = :u
        AND strftime('%Y', date) = :y
        AND strftime('%m', date) = :m
      GROUP BY category
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      'u' => $c['user_id'],
      'y' => str_pad((string)$c['year'], 4, '0', STR_PAD_LEFT),
      'm' => str_pad((string)$c['month'], 2, '0', STR_PAD_LEFT),
    ]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['category']] = (float)$row['avg'];
    }
    return $result;
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
           $sql = "
      SELECT SUM(amount_cents) AS total
      FROM expenses
      WHERE user_id = :u
        AND strftime('%Y', date) = :y
        AND strftime('%m', date) = :m
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      'u' => $c['user_id'],
      'y' => str_pad((string)$c['year'], 4, '0', STR_PAD_LEFT),
      'm' => str_pad((string)$c['month'], 2, '0', STR_PAD_LEFT),
    ]);
    return (float)($stmt->fetchColumn() ?? 0);
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            (int)$data['id'],
            (int)$data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            (int)$data['amount_cents'],
            $data['description'],
        );
    }
}
