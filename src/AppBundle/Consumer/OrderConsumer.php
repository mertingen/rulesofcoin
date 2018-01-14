<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/14/18
 * Time: 12:01 PM
 */

namespace AppBundle\Consumer;


use AppBundle\Entity\Bid;
use Binance\API;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class OrderConsumer implements ConsumerInterface
{
    private $entityManager;
    private $binanceService;

    /**
     * RuleConsumer constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg)
    {
        $order = unserialize($msg->body);
        if ($order) {
            $binanceApiKey = $order->getRule()->getUser()->getBinanceApiKey();
            $binanceSecretKey = $order->getRule()->getUser()->getBinanceSecretKey();

            $binanceApi = new API($binanceApiKey, $binanceSecretKey);

            $orderStatus = $binanceApi->orderStatus($order->getRule()->getSymbol(), $order->getOrderId());

            $bid = new Bid();
            $bid->setStatus($orderStatus['status']);
            $bid->setExecutedQuantity($orderStatus['executedQty']);

            $this->entityManager->persist($bid);
            $this->entityManager->flush();
        }
    }
}