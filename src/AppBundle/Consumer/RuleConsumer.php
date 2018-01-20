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
use AppBundle\Service\RedisService;
use AppBundle\Service\UserBinanceService;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RuleConsumer implements ConsumerInterface
{
    private $entityManager;
    private $binanceService;
    private $userBinanceService;
    private $redisService;

    /**
     * RuleConsumer constructor.
     * @param EntityManagerInterface $entityManager
     * @param BinanceService $binanceService
     * @param UserBinanceService $userBinanceService
     * @param RedisService $redisService
     */
    public function __construct(EntityManagerInterface $entityManager, BinanceService $binanceService, UserBinanceService $userBinanceService, RedisService $redisService)
    {
        $this->entityManager = $entityManager;
        $this->binanceService = $binanceService;
        $this->userBinanceService = $userBinanceService;
        $this->redisService = $redisService;
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
}