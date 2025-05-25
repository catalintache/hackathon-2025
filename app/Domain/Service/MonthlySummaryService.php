<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        // TODO: compute expenses total for year-month for a given user
        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $lastDay);

        $criteria = [
            'user_id'   => $user->getId(),
            'date_from' => $from,
            'date_to'   => $to,
        ];


        $totalCount = $this->expenses->countBy($criteria);


        $items = $this->expenses->findBy($criteria, 0, $totalCount);


        $sumCents = 0;
        foreach ($items as $e) {
            /** @var \App\Domain\Entity\Expense $e */
            $sumCents += $e->getAmountCents();
        }

        return $sumCents / 100.0;
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {

        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $lastDay);

        $criteria = [
            'user_id'   => $user->getId(),
            'date_from' => $from,
            'date_to'   => $to,
        ];

        $totalCount = $this->expenses->countBy($criteria);
        $items      = $this->expenses->findBy($criteria, 0, $totalCount);


        $byCat = [];
        $grandTotal = 0;
        foreach ($items as $e) {
            $cat = $e->getCategory();
            $amt = $e->getAmountCents();
            $byCat[$cat]['sumCents'] = ($byCat[$cat]['sumCents'] ?? 0) + $amt;
            $grandTotal += $amt;
        }

   
        $out = [];
        foreach ($byCat as $cat => $data) {
            $value = $data['sumCents'] / 100.0;
            $perc  = $grandTotal > 0
                   ? (int) floor( ($data['sumCents'] / $grandTotal) * 100 )
                   : 0;
            $out[$cat] = [
                'value'      => $value,
                'percentage' => $perc,
            ];
        }

        return $out;
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user
        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $to   = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $lastDay);

        $criteria = [
            'user_id'   => $user->getId(),
            'date_from' => $from,
            'date_to'   => $to,
        ];

        $totalCount = $this->expenses->countBy($criteria);
        $items      = $this->expenses->findBy($criteria, 0, $totalCount);

  
        $byCat = [];
        foreach ($items as $e) {
            $cat = $e->getCategory();
            $amt = $e->getAmountCents();
            $byCat[$cat]['sumCents']   = ($byCat[$cat]['sumCents']   ?? 0) + $amt;
            $byCat[$cat]['countItems'] = ($byCat[$cat]['countItems'] ?? 0) + 1;
        }


        $out = [];
        foreach ($byCat as $cat => $data) {
            $avgCents = $data['countItems'] > 0
                      ? (int) floor($data['sumCents'] / $data['countItems'])
                      : 0;
            $out[$cat] = $avgCents / 100.0;
        }

        return $out;
    }
}
