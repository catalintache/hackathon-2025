<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        $userId = $user->getId();          
        if ($userId === null) {
            throw new \RuntimeException("User must have an ID to list expenses");
        }

        $offset = ($pageNumber - 1) * $pageSize;
        $criteria = [
    'user_id'   => $user->getId(),
    'date_from' => sprintf('%04d-%02d-01', $year, $month),
    'date_to'   => sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year)),
];

        $items      = $this->expenses->findBy($criteria, $offset, $pageSize);
        $totalCount = $this->expenses->countBy($criteria);
        $totalPages = (int)ceil($totalCount / $pageSize);

        return [
            'items'       => $items,
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $pageNumber,
        ];
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist

        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->getId(), $date, $category, (int)$round(amount * 100), $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
