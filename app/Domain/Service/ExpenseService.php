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

        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $lastDay);

        $offset = ($pageNumber - 1) * $pageSize;
        $criteria = [
        'user_id'   => $userId,
        'date_from' => $from,
        'date_to'   => $to,
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
        $userId = $user->getId();
    if ($userId === null) {
        throw new \RuntimeException('User must have an ID to create an expense');
    }

    $amountCents = (int) round($amount * 100);

    $expense = new Expense(
        null,
        $userId,
        $date,
        $category,
        $amountCents,
        $description
    );

    $this->expenses->save($expense);
    }

    public function listExpenditureYears(User $user): array
    {
        return $this->expenses->listExpenditureYears($user);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
         $expense->date        = $date;
    $expense->category    = $category;
    $expense->amountCents = (int) round($amount * 100);
    $expense->description = $description;

    // persist (PdoExpenseRepository->save will run the UPDATE branch)
    $this->expenses->save($expense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        $stream = $csvFile->getStream();
        $imported = 0;


    while (! $stream->eof()) {
        $line = trim($stream->read(4096));
        if ($line === '') {
            continue;
        }
        $cols = str_getcsv($line);
        if (count($cols) < 4) {
            continue; 
        }
        [$dateStr, $category, $amountStr, $description] = $cols;

        try {
            $date = new \DateTimeImmutable($dateStr);
            $amountCents = (int) round((float)$amountStr * 100);
        } catch (\Exception $e) {
            continue; 
        }

        $expense = new Expense(
            null,
            $user->getId(),
            $date,
            $category,
            $amountCents,
            $description,
        );
        $this->expenses->save($expense);
        $imported++;
    }

    return $imported;
    }
}
