<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/6/18
 * Time: 6:51 PM
 */

namespace AppBundle\Service;


use AppBundle\Entity\Rule;
use AppBundle\Entity\User;
use Binance\API;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class BinanceService
{
    /**
     * @var API $binanceApi
     */
    private $binanceApi;
    private $entityManager;
    private $redisService;

    /**
     * BinanceService constructor.
     * @param API $binanceApi
     * @param null $binanceApiKey
     * @param null $binanceSecretKey
     * @param TokenStorage $tokenStorage
     * @param EntityManagerInterface $entityManager
     * @param RedisService $redisService
     */
    public function __construct($binanceApi, $binanceApiKey = NULL, $binanceSecretKey = NULL, TokenStorage $tokenStorage, EntityManagerInterface $entityManager, RedisService $redisService)
    {
        //$user = $tokenStorage->getToken()->getUser();
        //$this->binanceApi = new $binanceApi($user->getBinanceApiKey(), $user->getBinanceSecretKey());
        $this->binanceApi = new $binanceApi($binanceApiKey, $binanceSecretKey);
        $this->entityManager = $entityManager;
        $this->redisService = $redisService;
    }

    /**
     * @param null $symbol
     * @return array|bool
     */
    public function getCoinsWithPrices($symbol = NULL)
    {
        $coins = $this->binanceApi->prices();
        if ($symbol !== NULL && isset($coins[$symbol])) {
            return array('symbol' => $symbol, 'price' => $coins[$symbol]);
        }
        if ($coins && is_array($coins)) {
            $btcCoins = [];
            foreach ($coins as $key => $price) {
                if (strpos(strtolower($key), 'btc') > -1 && strtolower($key) != 'btcusdt') {
                    $btcCoins[$key] = $price;
                }
            }
            ksort($btcCoins);

            return $btcCoins;
        }
        return false;
    }

    /**
     * @param Rule $rule
     * @return Rule
     */
    public function upsertRule(Rule $rule)
    {
        $this->entityManager->persist($rule);
        $this->entityManager->flush();
        return $rule;
    }

    /**
     * @param array $where
     * @return Rule|bool|object
     */
    public function getRule($where = array())
    {
        $rule = $this->entityManager->getRepository('AppBundle:Rule')->findOneBy($where);
        if ($rule) {
            $haveParentOrChildRule = $this->checkRuleParentOrChild($rule);
            $rule->setHaveParentOrChildRule($haveParentOrChildRule);
            return $rule;
        } else {
            return false;
        }
    }


    /**
     * @param array $where
     * @param array $orderBy
     * @return array
     */
    public function getRules($where = array(), $orderBy = array())
    {
        $ruleRepo = $this->entityManager->getRepository('AppBundle:Rule');
        $allRules = $ruleRepo->findBy($where, $orderBy);
        $rules = array();
        foreach ($allRules as $rule) {
            $haveParentOrChildRule = $this->checkRuleParentOrChild($rule);
            $rule->setHaveParentOrChildRule($haveParentOrChildRule);
            $rules[] = $rule;
        }
        return $rules;
    }

    /**
     * @param Rule $rule
     * @return bool
     */
    public function removeRule(Rule $rule)
    {
        if (empty($rule)) {
            return false;
        }

        $this->entityManager->remove($rule);
        $this->entityManager->flush();
        return true;
    }

    /**
     * @param User $user
     */
    public function setRulesToRedis(User $user)
    {
        $rules = $this->getRules(array('isDone' => false, 'parentRule' => NULL));
        $data = array();
        /**
         * @var $rule Rule
         */
        foreach ($rules as $rule) {
            $data[$rule->getSymbol()][$rule->getId()] = array(
                'ruleId' => $rule->getId(),
                'ruleLimit' => $rule->getRuleLimit(),
                'stop' => $rule->getStop(),
                'binance_api_key' => $user->getBinanceApiKey(),
                'binance_secret_key' => $user->getBinanceSecretKey(),
                'btcPrice' => $rule->getBtcPrice(),
                'quantity' => $rule->getQuantity(),
                'stopType' => $rule->getStopType(),
                'type' => $rule->getType()
            );
        }
        $this->redisService->insert('rules', $data);

    }

    /**
     * @param $price
     * @return string
     */
    public function getBtcNumberFormat($price)
    {
        return number_format($price, 8, '.', '');
    }

    /**
     * @param array $where
     * @return mixed
     */
    public function getCountUserRulesBySymbol($where = array())
    {
        return $this->entityManager->getRepository('AppBundle:Rule')->countUserRulesBySymbol($where);
    }

    /**
     * @param Rule $rule
     * @return bool
     */
    public function checkRuleParentOrChild(Rule $rule)
    {
        $parentRule = $rule->getParentRule();
        $childRule = $this->getRule(
            array('parentRule' => $rule)
        );
        return ($parentRule || $childRule) ? true : false;

    }

}