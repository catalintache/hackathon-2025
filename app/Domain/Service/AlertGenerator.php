<?php

declare(strict_types=1);

namespace App\Domain\Service;

use Psr\Log\LoggerInterface;
use App\Domain\Entity\User;
use App\Domain\Service\MonthlySummaryService;

class AlertGenerator
{
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.

    private array $categoryBudgets;
     public function __construct(

        private MonthlySummaryService $summaryService,
        private LoggerInterface      $logger

    ) {
        $raw = getenv('CATEGORY_BUDGETS') ?: '';
        if ($raw && $decoded = json_decode($raw, true)) {
            $this->categoryBudgets = $decoded;
        } else {
            $this->categoryBudgets = [
                'Groceries' => 300.00,
                'Utilities' => 200.00,
                'Transport' => 500.00,
            ];
        }
    }

    public function generate(User $user, int $year, int $month): array
    {

        $alerts = [];
        $totals = $this->summaryService
                       ->computePerCategoryTotals($user, $year, $month);

        foreach ($this->categoryBudgets as $category => $budget) {

            $spent = $totals[$category] ?? 0.0;

            if ($spent > $budget) {
                $over = $spent - $budget;
                $msg  = sprintf(
                    '⚠ %s budget exceeded by %s €',
                    $category,
                    number_format($over, 2, ',', '.')
                );
                $alerts[] = $msg;
                $this->logger->warning($msg);
            }
        }

        return $alerts;
    }
}
