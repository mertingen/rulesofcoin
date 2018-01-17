<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/15/18
 * Time: 9:40 PM
 */

namespace AppBundle\EventListener;


use AppBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserBinanceApiKeysListener implements EventSubscriberInterface
{
    private $tokenStorage;
    private $router;

    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if ($token !== null) {
            $user = $this->tokenStorage->getToken()->getUser();
            if ($user instanceof User) {
                if (!$user->getBinanceSecretKey() || !$user->getBinanceSecretKey()) {
                    $route = $event->getRequest()->get('_route');
                    if ($route == 'key-add' || $route == 'post-key-add') {
                        return;
                    }
                    $url = $this->router->generate('key-add');
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'kernel.request' => 'onKernelRequest'
        );
    }
}