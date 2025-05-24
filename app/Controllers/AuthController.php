<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data     = (array)$request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $errors   = [];

        if (strlen($username) < 5) {
            $errors['username'] = 'Username must be at least 5 characters.';
        }
        if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must be at least 8 chars and contain a number.';
        }
        if ($this->authService->userExists($username)) {
            $errors['username'] = 'This username is already taken.';
        }

        if (!empty($errors)) {
            return $this->render(
                $response->withStatus(422),
                'auth/register.twig',
                ['errors' => $errors, 'old' => $data]
            );
        }

        $this->authService->register($username, $password);
        $this->logger->info("User registered: {$username}");


        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data     = (array)$request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $errors   = [];

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        $user = null;
        if (empty($errors)) {
            $user = $this->authService->login($username, $password);
            if (! $user) {
                $errors['credentials'] = 'Username or password is invalid.';
            }
        }

        if (!empty($errors)) {
            return $this->render(
                $response->withStatus(401),
                'auth/login.twig',
                ['errors' => $errors, 'old' => $data]
            );
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $this->logger->info("User logged in: {$username}");


        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_unset();
        session_destroy();
        $this->logger->info('User logged out');

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
