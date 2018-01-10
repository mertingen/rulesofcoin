<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/6/18
 * Time: 4:02 PM
 */

namespace AppBundle\Service;


use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private $entityManager;

    /**
     * UserService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param null $userId
     * @return User
     */
    public function get($userId = null)
    {
        return $this->entityManager->getRepository('AppBundle:User')->findOneBy(array('id' => $userId));
    }

    /**
     * @param User $user
     */
    public function upsert(User $user)
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

}