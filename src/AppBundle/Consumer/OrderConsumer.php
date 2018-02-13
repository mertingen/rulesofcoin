<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/14/18
 * Time: 12:01 PM
 */

namespace AppBundle\Consumer;


use AppBundle\Entity\Bid;
use AppBundle\Service\TwitterService;
use AppBundle\Service\UserBinanceService;
use Binance\API;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class OrderConsumer implements ConsumerInterface
{
    private $entityManager;
    private $userBinanceService;
    private $twitterService;
    private $container;

    /**
     * RuleConsumer constructor.
     * @param TwitterService $twitterService
     * @param EntityManagerInterface $entityManager
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(TwitterService $twitterService, EntityManagerInterface $entityManager, \Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container = $container;
        $this->userBinanceService = $container->get('user_binance_service');
        $this->twitterService = $twitterService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg)
    {
        $data = unserialize($msg->body);
        if (!empty($data)) {
            $binanceApiKey = $data['binanceApiKey'];
            $binanceSecretKey = $data['binanceSecretKey'];

            $this->userBinanceService->connect($binanceApiKey, $binanceSecretKey);
            $orderData = array(
                'symbol' => $data['symbol'],
                'orderId' => $data['orderId']
            );
            $orderStatus = $this->userBinanceService->getOrderStatus($orderData);

            if (isset($orderStatus['status']) && $orderStatus['status'] != 'NEW') {
                $bid = $this->entityManager->getRepository('AppBundle:Bid')->findOneBy(array('id' => $data['bidId']));
                if ($bid) {
                    $bid->setStatus($orderStatus['status']);
                    $bid->setExecutedQuantity($orderStatus['executedQty']);

                    $userTwitterScreenName = $data['twitterScreenName'];
                    if ($userTwitterScreenName) {
                        $twitterMessageData = array(
                            'screenName' => $userTwitterScreenName,
                            'symbol' => $data['symbol'],
                            'status' => $bid->getStatus(),
                            'quantity' => $bid->getExecutedQuantity(),
                            'buyLimit' => $data['buyLimit']
                        );
                        $this->sendTwitterNotification($twitterMessageData);
                    }

                    $this->entityManager->persist($bid);
                    $this->entityManager->flush();
                }
            }
        }
    }

    /**
     * @param array $data
     */
    public function sendTwitterNotification($data = array())
    {
        $message = "A order is done! [STATUS:" . $data['status'] . "] - [SYMBOL:" . $data['symbol'] . "] - [QUANTITY:" . $data['quantity'] . "] - [LIMIT:" . $data['buyLimit'] . "]";
        $this->twitterService->connect(
            $this->container->getParameter('twitter')
        );
        $this->twitterService->sendMessage($data['screenName'], $message);
    }

}