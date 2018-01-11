<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }
        $errors = $authenticationUtils->getLastAuthenticationError();
        $lastUserName = $authenticationUtils->getLastUsername();
        return $this->render('@App/Login/login.html.twig',
            array(
                'errors' => $errors,
                'lastUserName' => $lastUserName
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/signup", methods={"GET"}, name="signup")
     */
    public function singUpAction()
    {
        return $this->render('@App/Signup/signup.html.twig');
    }

    /**
     * @Route("/signup", methods={"POST"}, name="signup-post")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function singUpPostAction(Request $request, EntityManagerInterface $entityManager)
    {
        $username = $request->request->get('username', null);
        $password = $request->request->get('password', null);
        $confirmPassword = $request->request->get('confirm_password', null);

        if (is_null($username) || is_null($password) || is_null($confirmPassword)) {
            die('Required fills...');
        }
        if ($password !== $confirmPassword) {
            die('Not valid password!');
        }

        $isValidUserName = $entityManager->getRepository('AppBundle:User')->findOneBy(array('username' => $username));
        if ($isValidUserName) {
            die('Not valid username!');
        }

        $passwordEncoder = $this->get('security.password_encoder');

        $user = new User();
        $user->setUsername($username);
        $user->setCreatedAt(new \DateTime());
        $user->setPassword($passwordEncoder->encodePassword($user, $password));
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {

    }

}
