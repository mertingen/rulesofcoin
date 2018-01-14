<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/8/18
 * Time: 12:57 PM
 */

namespace AppBundle\Consumer;


use AppBundle\Entity\Bid;
use AppBundle\Service\BinanceService;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RuleConsumer implements ConsumerInterface
{
    private $entityManager;
    private $binanceService;

    /**
     * RuleConsumer constructor.
     * @param EntityManagerInterface $entityManager
     * @param BinanceService $binanceService
     */
    public function __construct(EntityManagerInterface $entityManager, BinanceService $binanceService)
    {
        $this->entityManager = $entityManager;
        $this->binanceService = $binanceService;

    }

    public function execute(AMQPMessage $data)
    {
        try {
            $bid = unserialize($data->body);
            if (is_array($bid) && !empty($bid)) {
                $newBid = new Bid();
                $newBid->setOrderId($bid['orderId']);
                $newBid->setClientOrderId($bid['clientOrderId']);
                $newBid->setCreatedAt(new \DateTime());

                $rule = $this->binanceService->getRule(array('id' => $bid['ruleId']));
                $rule->setIsDone(true);

                $newBid->setRule($rule);
                $newBid->setStatus('NEW');
                $this->entityManager->persist($rule);
                $this->entityManager->persist($newBid);
                $this->entityManager->flush();
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            die;
        }
    }
}