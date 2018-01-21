<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/20/18
 * Time: 7:12 PM
 */

namespace AppBundle\Controller;


use Abraham\TwitterOAuth\TwitterOAuthException;
use AppBundle\Service\TwitterService;
use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SettingController
 * @package AppBundle\Controller
 * @Route("/twitter")
 * @Security("has_role('ROLE_USER')")
 */
class TwitterController extends Controller
{
    private $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /**
     * @param Request $request
     * @param TwitterService $twitterService
     * @param UserService $userService
     * @Route("/checkUser", methods={"GET"}, name="twitter-check-user")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function checkUserAction(Request $request, TwitterService $twitterService, UserService $userService)
    {
        $isDenied = $oauthVerifier = $request->query->get('denied');
        if ($isDenied) {
            $this->flashBag->add('error', 'Twitter login not successfully!');
            return $this->redirectToRoute('setting-add');
        }
        $oauthVerifier = $request->query->get('oauth_verifier');
        try {
            $twitterService->connect(
                $this->getParameter('twitter')
            );

            $twitterUser = $twitterService->getUser($oauthVerifier);
            $user = $userService->get($this->getUser()->getId());
            $user->setTwitterScreenName($twitterUser->screen_name);
            $userService->upsert($user);
            $this->flashBag->add('success', 'Twitter login successfully. If you want take notifications, you should follow Rulechain Twitter account.');
            return $this->redirectToRoute('setting-add');
        } catch (TwitterOAuthException $e) {
            dump($e->getMessage());
            die;
        }
    }


}