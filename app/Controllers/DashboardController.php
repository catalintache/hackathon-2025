<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Service\AlertGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        // injectează-ţi aici serviciile necesare ulterior
        private UserRepositoryInterface $users,
        private MonthlySummaryService $summaryService,
        private AlertGenerator $alertGenerator
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $year   = isset($params['year'])  ? (int) $params['year']  : (int) date('Y');
        $month  = isset($params['month']) ? (int) $params['month'] : (int) date('n');

        $userId = $_SESSION['user_id'] ?? null;
        if ($userId === null) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        $user = $this->users->find($userId);
        if (! $user) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $totalForMonth = $this->summaryService->computeTotalExpenditure($user, $year, $month);
        $totalsForCategories = $this->summaryService->computePerCategoryTotals($user, $year, $month);
        $averagesForCategories = $this->summaryService->computePerCategoryAverages($user, $year, $month);

        $alerts = [];
        if ($year === (int) date('Y') && $month === (int) date('n')) {
            $alerts = $this->alertGenerator->generate($user, $year, $month);
        }

    $currentYear = (int) date('Y');
    $years       = [
    $currentYear,
    $currentYear - 1,
    $currentYear - 2,
];

        return $this->render($response, 'dashboard.twig', [
            'selectedYear'          => $year,
            'selectedMonth'         => $month,
            'alerts'                => $alerts,
            'totalForMonth'         => $totalForMonth,
            'totalsForCategories'   => $totalsForCategories,
            'averagesForCategories' => $averagesForCategories,
            'years'                 => $years,
        ]);
    }
}
