<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;  
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly UserRepositoryInterface $users,
        private readonly ExpenseRepositoryInterface   $expenses,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        // TODO: obtain logged-in user ID from session

         $userId = $_SESSION['user_id'] ?? null;
        if ($userId === null) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

         $user = $this->users->find($userId);

        if (! $user) {
            session_destroy();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $qp        = $request->getQueryParams();
    $year      = (int)($qp['year']  ?? date('Y'));
    $month     = (int)($qp['month'] ?? date('n'));
    $page      = max(1, (int)($qp['page']     ?? 1));
    $pageSize  = (int)($qp['pageSize'] ?? self::PAGE_SIZE);

        $result = $this->expenseService->list(
         $user,
         $year,
         $month,
         $page,
         $pageSize
    );

    $years = $this->expenses->listExpenditureYears($user);

        return $this->render($response, 'expenses/index.twig', [
            'expenses'      => $result['items'],
            'totalCount'    => $result['totalCount'],
            'totalPages'    => $result['totalPages'],
            'currentPage'   => $result['currentPage'],
            'pageSize'      => $pageSize,
            'selectedYear'  => $year,
            'selectedMonth' => $month,
            'years'         => $years,
            
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        $categories = ['Food','Utilities','Entertainment','Transportation','Health'];
        return $this->render($response, 'expenses/create.twig', [
            'categories' => $categories,
            'old'        => [],      
            'errors'     => [],
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $user = $this->users->find($userId);
    if (! $user) {
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }


    $data = (array)$request->getParsedBody();
    $dateStr     = $data['date'] ?? '';
    $category    = trim($data['category'] ?? '');
    $amount      = (float)($data['amount'] ?? 0);
    $description = trim($data['description'] ?? '');


    $errors = [];
    if ($dateStr === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        $errors['date'] = 'Date invalid.';
    }
    if ($category === '') {
        $errors['category'] = 'Trebuie selectată o categorie.';
    }
    if ($amount <= 0) {
        $errors['amount'] = 'Suma trebuie > 0.';
    }
    if ($description === '') {
        $errors['description'] = 'Descrierea nu poate fi goală.';
    }

    if (! empty($errors)) {
  
        return $this->render(
            $response->withStatus(422),
            'expenses/create.twig',
            [
                'errors'     => $errors,
                'old'        => $data,
                'categories' => $this->getCategoriesConfig(),
            ]
        );
    }


    $date = new \DateTimeImmutable($dateStr.' 00:00:00');
    
    $this->expenseService->create($user, $amount, $description, $date, $category);


    $y = $date->format('Y');
$m = $date->format('n');
return $response
    ->withHeader('Location', "/expenses?year={$y}&month={$m}")
    ->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }


    $expenseId = (int) $routeParams['id'];
    $expense   = $this->expenses->find($expenseId);


    if (! $expense || $expense->userId !== $userId) {
        return $response->withStatus(403)->write('Forbidden');
    }

    $categories = [
        'groceries'     => 'Groceries',
        'utilities'     => 'Utilities',
        'transport'     => 'Transport',
        'entertainment' => 'Entertainment',
        'housing'       => 'Housing',
        'health'        => 'Healthcare',
        'other'         => 'Other',
    ];

    return $this->render($response, 'expenses/edit.twig', [
        'expense'    => $expense,
        'categories' => $categories,
        'errors'     => [],  
    ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $userId    = $_SESSION['user_id'] ?? null;
    $expenseId = (int)$routeParams['id'];

    if ($userId === null) {
        return $response->withHeader('Location','/login')->withStatus(302);
    }
    $user    = $this->users->find($userId);
    $expense = $this->expenses->find($expenseId);
    if (! $user || ! $expense || $expense->userId !== $userId) {
        $response = $response->withStatus(403);
$response->getBody()->write('Forbidden');
return $response;;
    }


    $data        = (array)$request->getParsedBody();
    $errors      = [];
    $dateStr     = $data['date'] ?? '';
    $category    = trim($data['category'] ?? '');
    $amount      = (float)($data['amount'] ?? 0);
    $description = trim($data['description'] ?? '');

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        $errors['date'] = 'Invalid date.';
    }
    if ($category === '') {
        $errors['category'] = 'Required.';
    }
    if ($amount <= 0) {
        $errors['amount'] = 'Must be > 0.';
    }
    if ($description === '') {
        $errors['description'] = 'Cannot be empty.';
    }

    if (! empty($errors)) {

        $categories = ['Food','Utilities','Entertainment','Transportation','Health','Other'];
        return $this->render(
            $response->withStatus(422),
            'expenses/edit.twig',
            [
                'expense'    => $expense,
                'categories' => $categories,
                'errors'     => $errors,
            ]
        );
    }


    $date = new \DateTimeImmutable($dateStr . ' 00:00:00');
    $this->expenseService->update(
        $expense,
        $amount,
        $description,
        $date,
        $category
    );

    return $response->withHeader('Location','/expenses')->withStatus(302);
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return $response->withHeader('Location','/login')->withStatus(302);
    }

    $expenseId = (int)$routeParams['id'];
    $expense   = $this->expenses->find($expenseId);


    if (! $expense || $expense->userId !== $userId) {
        return $response->withStatus(403)->write('Forbidden');
    }

    
    $this->expenses->delete($expenseId);

    return $response->withHeader('Location','/expenses')->withStatus(302);
    }


public function import(Request $request, Response $response): Response
{
  
    $userId = $_SESSION['user_id'] ?? null;
    if (! $userId) {
        return $response->withHeader('Location','/login')->withStatus(302);
    }
    $user = $this->users->find($userId);
    if (! $user) {
        session_destroy();
        return $response->withHeader('Location','/login')->withStatus(302);
    }

 
   $uploaded = $request->getUploadedFiles()['csv'] ?? null;
        if (! $uploaded instanceof UploadedFileInterface) {
        $this->logger->warning("CSV import failed: no file uploaded");
        return $response->withHeader('Location','/expenses')->withStatus(302);
    }

    
    $content   = $uploaded->getStream()->__toString();
    $lines     = explode("\n", $content);
    $imported  = 0;
    $skipped   = [];
$validCats = ['groceries','utilities','transport','entertainment','housing','health','other'];

    foreach ($lines as $i => $line) {
        $line = trim($line);
        if ($line === '') {
            continue;                    
        }

  
        if ($i === 0 && stripos($line, 'Description,') === 0) {
            continue;
        }

  
        [$description, $amountStr, $dateStr, $category] = str_getcsv($line);

  
        $dt = \DateTimeImmutable::createFromFormat('m/d/Y', $dateStr);
        if (! $dt) {
            $skipped[] = "line ".($i+1)." bad date “{$dateStr}”";
            continue;
        }
 
        $dt = $dt->setTime(0,0,0);

   
        if (! in_array($category, $validCats, true)) {
            $skipped[] = "line ".($i+1)." unknown category “{$category}”";
            continue;
        }

   
        $amountCents = (int)round((float)$amountStr * 100);

        if ($this->expenses->existsExact(
            $userId,
            $dt->format('Y-m-d H:i:s'),
            $description,
            $amountCents,
            $category
        )) {
            $skipped[] = "line ".($i+1)." duplicate";
            continue;
        }

        $this->expenseService->create(
            $user,
            (float)$amountStr,
            $description,
            $dt,
            $category
        );
        $imported++;
    }

    foreach ($skipped as $msg) {
        $this->logger->warning("CSV import skipped: {$msg}");
    }
    $this->logger->info("CSV import finished: {$imported} imported, ".count($skipped)." skipped.");

    $qp    = $request->getQueryParams();
    $year  = isset($qp['year'])  ? (int)$qp['year']  : date('Y');
    $month = isset($qp['month']) ? (int)$qp['month'] : date('n');

    return $response
        ->withHeader('Location', "/expenses?year={$year}&month={$month}")
        ->withStatus(302);
}

}
