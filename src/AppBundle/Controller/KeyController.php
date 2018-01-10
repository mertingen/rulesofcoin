<?php

namespace AppBundle\Controller;

use AppBundle\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class KeyController
 * @package AppBundle\Controller
 * @Route("/key")
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
     * @Route("add", methods={"POST"}, name="post-key-add")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postUpsertAction(Request $request, UserService $userService)
    {
        $binanceApiKey = $request->request->get('binance-api-key');
        $binanceSecretKey = $request->request->get('binance-secret-key');

        if (empty($binanceApiKey) || empty($binanceSecretKey)) {
            dump('keys not valid!');
            die;
        }

        if (strlen($binanceApiKey) != 64 || strlen($binanceSecretKey) != 64) {
            dump('keys not valid!');
            die;
        }

        $user = $userService->get($this->getUser()->getId());
        if (!$user) {
            dump('user not found!');
            die;
        }
        $user->setBinanceApiKey($binanceApiKey);
        $user->setBinanceSecretKey($binanceSecretKey);

        $userService->upsert($user);
        return $this->redirectToRoute('key-add');
    }

}
