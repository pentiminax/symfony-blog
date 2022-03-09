<?php

namespace App\EventListener;

use App\Service\DatabaseService;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\RouterInterface;

class ExceptionListener
{
    public function __construct(
        private DatabaseService $databaseService,
        private RouterInterface $router
    ) {}

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ConnectionException || $exception instanceof TableNotFoundException) {
            $this->databaseService->createDatabase();
            $response = new RedirectResponse($this->router->generate('welcome'));
            $event->setResponse($response);
        }
    }
}