<?php

namespace AppBundle\Controller;

use AppBundle\Service\TwitterService;
use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class SettingController
 * @package AppBundle\Controller
 * @Route("/setting")
 * @Security("has_role('ROLE_USER')")
 */
class SettingController extends Controller
{
    private $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /**
     * @Route("/add", methods={"GET"},name="setting-add")
     * @param TwitterService $twitterService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(TwitterService $twitterService)
    {
        $twitterService->connect(
            $this->getParameter('twitter_consumer_key'),
            $this->getParameter('twitter_consumer_secret_key'),
            $this->getParameter('twitter_access_token'),
            $this->getParameter('twitter_access_secret_token')
        );
        //$twitterService->getFollowers(); die;
        $oauthUrl = $twitterService->getUrl();
        return $this->render('@App/Setting/add.html.twig',
            array('twitterOauthUrl' => $oauthUrl)
        );
    }

    /**
     * @param Request $request
     * @param UserService $userService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("add", methods={"POST"}, name="post-setting-add")
     */
    public function postUpsertAction(Request $request, UserService $userService)
    {
        $binanceApiKey = $request->request->get('binance-api-key');
        $binanceSecretKey = $request->request->get('binance-secret-key');

        if (empty($binanceApiKey) || empty($binanceSecretKey) || strlen($binanceApiKey) != 64 || strlen($binanceSecretKey) != 64) {
            $this->flashBag->add('error', 'Keys are not valid!');
            return $this->redirectToRoute('setting-add');
        }

        $user = $userService->get($this->getUser()->getId());
        $user->setBinanceApiKey($binanceApiKey);
        $user->setBinanceSecretKey($binanceSecretKey);

        $userService->upsert($user);
        return $this->redirectToRoute('setting-add');
    }

}
