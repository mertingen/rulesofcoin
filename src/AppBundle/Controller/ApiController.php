<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/26/18
 * Time: 5:22 PM
 */

namespace AppBundle\Controller;


use AppBundle\Entity\User;
use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class BinanceApiController
 * @package AppBundle\Controller
 * @Route("/api")
 * @Security("has_role('ROLE_USER')")
 */
class ApiController extends Controller
{
    /**
     * @Route("/removeUserTwitterScreenName/{id}", methods={"GET"}, name="api-remove-user-twitter-screen-name")
     * @param User $user
     * @param UserService $userService
     * @return JsonResponse
     */
    public function removeUserTwitterScreenNameAction(User $user, UserService $userService)
    {
        if ($this->getUser()->getId() != $user->getId()){
            return new JsonResponse(array('error' => true, 'message' => 'User not found!'));
        }

        $user->setTwitterScreenName(NULL);
        $userService->upsert($user);

        return new JsonResponse(array('error' => false, 'message' => 'Twitter connection successfully unlinked. You will not take notification.'));
    }

}