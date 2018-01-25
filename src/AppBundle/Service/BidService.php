<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/25/18
 * Time: 9:09 PM
 */

namespace AppBundle\Service;


use Doctrine\ORM\EntityManagerInterface;

class BidService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function get($bidId = '')
    {
        if (!$bidId) {
            return false;
        }
        $bidRepo = $this->entityManager->getRepository('AppBundle:Bid');
        return $bidRepo->findOneBy(array('id' => $bidId));
    }

    public function getAll($where = array())
    {
        $bidRepo = $this->entityManager->getRepository('AppBundle:Bid');
        return $bidRepo->findBy($where);
    }

}