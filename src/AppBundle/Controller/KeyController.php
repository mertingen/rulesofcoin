<?php

namespace AppBundle\Controller;

use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class KeyController
 * @package AppBundle\Controller
 * @Route("/key")
 * @Security("has_role('ROLE_USER')")
 */
class KeyController extends Controller
{
    /**
     * @Route("/add", methods={"GET"},name="key-add")
     */
    public function addAction()
    {
        return $this->render('@App/Key/add.html.twig', array(// ...
        ));
    }

    /**
     * @param Request $request
     * @param UserService $userService
     * @param FlashBagInterface $flashBag
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("add", methods={"POST"}, name="post-key-add")
     */
    public function postUpsertAction(Request $request, UserService $userService, FlashBagInterface $flashBag)
    {
        $binanceApiKey = $request->request->get('binance-api-key');
        $binanceSecretKey = $request->request->get('binance-secret-key');

        if (empty($binanceApiKey) || empty($binanceSecretKey) || strlen($binanceApiKey) != 64 || strlen($binanceSecretKey) != 64) {
            $flashBag->add('error', 'Keys are not valid!');
            return $this->redirectToRoute('key-add');
        }

        $user = $userService->get($this->getUser()->getId());
        $user->setBinanceApiKey($binanceApiKey);
        $user->setBinanceSecretKey($binanceSecretKey);

        $userService->upsert($user);
        return $this->redirectToRoute('key-add');
    }

}
