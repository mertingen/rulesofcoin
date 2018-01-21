<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/8/18
 * Time: 12:57 PM
 */

namespace AppBundle\Consumer;


use AppBundle\Entity\Bid;
use AppBundle\Entity\Rule;
use AppBundle\Service\BinanceService;
use AppBundle\Service\TwitterService;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RuleConsumer implements ConsumerInterface
{
    private $entityManager;
    private $binanceService;
    private $userBinanceService;
    private $redisService;
    private $twitterService;
    private $container;

    /**
     * RuleConsumer constructor.
     * @param BinanceService $binanceService
     * @param TwitterService $twitterService
     * @param EntityManagerInterface $entityManager
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(BinanceService $binanceService, TwitterService $twitterService, EntityManagerInterface $entityManager, \Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container = $container;
        $this->binanceService = $binanceService;
        $this->userBinanceService = $container->get('user_binance_service');
        $this->redisService = $container->get('redis_service');
        $this->twitterService = $twitterService;
        $this->entityManager = $entityManager;
    }

    public function execute(AMQPMessage $data)
    {
        try {
            $bid = unserialize($data->body);
            if (is_array($bid) && !empty($bid) && !empty($bid['orderId'])) {
                $newBid = new Bid();
                $newBid->setOrderId($bid['orderId']);
                $newBid->setClientOrderId($bid['clientOrderId']);
                $newBid->setCreatedAt(new \DateTime());
                $newBid->setExecutedQuantity($bid['executedQuantity']);

                $rule = $this->binanceService->getRule(array('id' => $bid['ruleId']));
                $rule->setIsDone(true);
                $this->entityManager->persist($rule);
                $this->entityManager->flush();

                $keys = array(
                    'binanceApiKey' => $bid['binanceApiKey'],
                    'binanceSecretKey' => $bid['binanceSecretKey'],
                    'user' => $rule->getUser()
                );

                $this->checkUserRules($keys);
                $userTwitterScreenName = $rule->getUser()->getTwitterScreenName();
                if ($userTwitterScreenName) {
                    $twitterMessageData = array(
                        'screenName' => $userTwitterScreenName,
                        'symbol' => $rule->getSymbol(),
                        'quantity' => $newBid->getExecutedQuantity(),
                        'buyLimit' => $rule->getBuyLimit()
                    );
                    $this->sendTwitterNotification($twitterMessageData);
                }

                $newBid->setRule($rule);
                $newBid->setStatus('NEW');
                $this->entityManager->persist($newBid);
                $this->entityManager->flush();
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            die;
        }
    }

    /**
     * @param array $data
     */
    public function checkUserRules($data = array())
    {
        $this->userBinanceService->connect($data['binanceApiKey'], $data['binanceSecretKey']);
        $userBtc = $this->userBinanceService->getUserBtcPrice();
        $userRules = $this->binanceService->getRules(array('user' => $data['user'], 'isDone' => false));
        $allRules = $this->redisService->get('rules');
        /**
         * @var Rule $userRule
         */
        foreach ($userRules as $userRule) {
            if ($userRule->getBtcPrice() > $userBtc) {
                $this->entityManager->remove($userRule);
                $this->entityManager->flush();
                unset($allRules[$userRule->getSymbol()][$userRule->getId()]);
                $this->redisService->insert('rules', $allRules);
            }
        }
    }

    /**
     * @param array $data
     */
    public function sendTwitterNotification($data = array())
    {
        $message = "A rule is done! [SYMBOL:" . $data['symbol'] . "] - [QUANTITY:" . $data['quantity'] . "] - [LIMIT:" . $data['buyLimit'] . "]";
        $this->twitterService->connect(
            $this->container->getParameter('twitter')
    );
        $this->twitterService->sendMessage($data['screenName'], $message);
    }
}