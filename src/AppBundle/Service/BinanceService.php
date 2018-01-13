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
        $user = $tokenStorage->getToken()->getUser();
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
                if (strpos(strtolower($key), 'btc') > -1) {
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
     * @return Rule|null|object
     */
    public function getRule($where = array())
    {
        $ruleRepo = $this->entityManager->getRepository('AppBundle:Rule');
        return $ruleRepo->findOneBy($where);
    }


    /**
     * @param array $where
     * @return Rule|null|object
     */
    public function getRules($where = array())
    {
        $ruleRepo = $this->entityManager->getRepository('AppBundle:Rule');
        return $ruleRepo->findBy($where);
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
        $rules = $this->getRules();
        $data = array();
        /**
         * @var $rule Rule
         */
        foreach ($rules as $rule) {
            $data[$rule->getSymbol()][] = array(
                'ruleId' => $rule->getId(),
                'buyLimit' => $rule->getBuyLimit(),
                'stop' => $rule->getStop(),
                'binance_api_key' => $user->getBinanceApiKey(),
                'binance_secret_key' => $user->getBinanceSecretKey(),
                'btcPrice' => $rule->getBtcPrice()
            );
        }
        $this->redisService->insert('rules', $data);

    }

    public function getUserBtcPrice(User $user)
    {
        $userBinanceApi = new API(
            $user->getBinanceApiKey(),
            $user->getBinanceSecretKey()
        );
        $btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        return $btcAvailable;
    }

    /**
     * @param User $user
     * @param int $price
     * @param string $symbol
     * @return array
     */
    public function getSymbolQuantityByBtc(User $user, $price = 0, $symbol = '')
    {
        $userBinanceApi = new API(
            $user->getBinanceApiKey(),
            $user->getBinanceSecretKey()
        );
        $btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        $quantity = intval($btcAvailable / $price);
        return $quantity;
    }

    /**
     * @param $price
     * @return string
     */
    public function getBtcNumberFormat($price)
    {
        return number_format($price, 8, '.', '');

    }

}