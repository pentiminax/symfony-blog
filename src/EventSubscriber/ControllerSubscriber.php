<?php

namespace App\EventSubscriber;

use App\Model\WelcomeModel;
use App\Service\OptionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class ControllerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OptionService $optionService,
        private RouterInterface $router
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onControllerEvent'
        ];
    }

    public function onControllerEvent(RequestEvent $event): void
    {
        $route = $event->getRequest()->attributes->getAlpha('_route');

        if ('welcome' !== $route && !$this->optionService->getValue(WelcomeModel::SITE_INSTALLED_NAME)) {
            $event->setResponse(new RedirectResponse($this->router->generate('welcome')));
        }
    }
}