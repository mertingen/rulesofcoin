<?php

namespace AppBundle\Command;

use AppBundle\Service\UserBinanceService;
use Binance\API;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BinanceRuleCheckCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('binance:rule:check')
            ->setDescription('Listening the Binance coins...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symbols = $this->getSymbols();
        if (!empty($symbols)) {
            $binanceApi = new API(
                $this->getContainer()->getParameter('binance_app_api_key'),
                $this->getContainer()->getParameter('binance_app_secret_key')
            );
            $binanceApi->trades($symbols, function ($api, $symbol, $trades) {
                $rules = $this->getRedisService()->get('rules');
                if (isset($rules[$symbol])) {
                    foreach ($rules[$symbol] as $ruleId => $symbolRule) {
                        $quantity = intval($symbolRule['quantity']);
                        if (isset($symbolRule['stop']) && is_numeric($symbolRule['stop']) && $symbolRule['stop'] > 0 && isset($symbolRule['stopType'])) {
                            if ($symbolRule['stopType'] == 'smaller' && $trades['price'] <= $symbolRule['stop']) {
                                unset($rules[$symbol][$ruleId]);
                                $this->getRedisService()->insert('rules', $rules);
                                $this->buy($symbolRule, $symbol, $quantity);
                                echo '[' . $symbol . ']' . ' - ' . '[STOP-LIMIT-SMALLER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['buyLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . '[DATE:' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
                            } elseif ($symbolRule['stopType'] == 'greater' && $trades['price'] >= $symbolRule['stop']) {
                                unset($rules[$symbol][$ruleId]);
                                $this->getRedisService()->insert('rules', $rules);
                                $this->buy($symbolRule, $symbol, $quantity);
                                echo '[' . $symbol . ']' . ' - ' . '[STOP-LIMIT-GREATER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['buyLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . '[DATE:' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
                            }
                        } else {
                            if ($trades['price'] <= $symbolRule['buyLimit']) {
                                unset($rules[$symbol][$ruleId]);
                                $this->getRedisService()->insert('rules', $rules);
                                $this->buy($symbolRule, $symbol, $quantity);
                                echo '[' . $symbol . ']' . ' - ' . '[LIMIT]' . ' - ' . '[' . $quantity . ']' . ' - ' . '[LIMIT:' . $symbolRule['buyLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . '[DATE:' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
                            }
                        }
                    }
                }
            });
        } //else {
        //$output->write('Rules not found!!!');
        //}
    }

    /**
     * @param array $rule
     * @param string $symbol
     * @param int $quantity
     */
    public function buy($rule = array(), $symbol = '', $quantity = 0)
    {
        $userBinanceService = $this->getUserBinanceSevice();
        $userBinanceService->connect($rule['binance_api_key'], $rule['binance_secret_key']);
        $buyData = array(
            'symbol' => $symbol,
            'quantity' => $quantity,
            'limit' => $rule['buyLimit']
        );
        $result = $userBinanceService->buy($buyData);
        //$btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        //$quantity = intval($rule['btcPrice'] / $trades['price']);
        if (is_array($result)) {
            if (array_key_exists('code', $result) && is_numeric($result['code'])) {
                echo '[ERROR] -> ' . $result['msg'] . PHP_EOL;
            } else {
                $order = array(
                    'ruleId' => $rule['ruleId'],
                    'orderId' => $result['orderId'],
                    'clientOrderId' => $result['clientOrderId'],
                    'createdAt' => new \DateTime(),
                    'executedQuantity' => $result['executedQty'],
                    'binanceApiKey' => $rule['binance_api_key'],
                    'binanceSecretKey' => $rule['binance_secret_key']
                );
                $this->getMqProducer()->publish(serialize($order));
            }
        }
    }

    /**
     * @param array $rule
     * @param string $symbol
     * @param int $quantity
     */
    public function sell($rule = array(), $symbol = '', $quantity = 0)
    {
        $userBinanceService = $this->getUserBinanceSevice();
        $userBinanceService->connect($rule['binance_api_key'], $rule['binance_secret_key']);
        $buyData = array(
            'symbol' => $symbol,
            'quantity' => $quantity,
            'limit' => $rule['buyLimit']
        );
        $result = $userBinanceService->sell($buyData);
        //$btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        //$quantity = intval($rule['btcPrice'] / $trades['price']);
        if (is_array($result)) {
            if (array_key_exists('code', $result) && is_numeric($result['code'])) {
                echo '[ERROR] -> ' . $result['msg'] . PHP_EOL;
            } else {
                $order = array(
                    'ruleId' => $rule['ruleId'],
                    'orderId' => $result['orderId'],
                    'clientOrderId' => $result['clientOrderId'],
                    'createdAt' => new \DateTime(),
                    'executedQuantity' => $result['executedQty'],
                    'binanceApiKey' => $rule['binance_api_key'],
                    'binanceSecretKey' => $rule['binance_secret_key']
                );
                $this->getMqProducer()->publish(serialize($order));
            }
        }
    }

    /**
     * @return \AppBundle\Service\RedisService|mixed|object
     */
    public function getRedisService()
    {
        return $this->getContainer()->get('redis_service');
    }


    public function getMqProducer()
    {
        return $this->getContainer()->get('old_sound_rabbit_mq.rule_producer');
    }

    /**
     * @return mixed
     */
    public function getRules()
    {
        return $this->getRedisService()->get('rules');
    }

    public function getUserBinanceSevice()
    {
        return $this->getContainer()->get('user_binance_service');
    }

    /**
     * @return mixed
     */
    public function getSymbols()
    {
        return $this->getRedisService()->get('symbols');
    }


    public function testBuy()
    {
        $order = array(
            'ruleId' => 63,
            'orderId' => 'testOrderId',
            'clientOrderId' => 'testClientOrderId',
            'createdAt' => new \DateTime(),
            'executedQuantity' => 1000,
            'binanceApiKey' => $this->getContainer()->getParameter('binance_app_api_key'),
            'binanceSecretKey' => $this->getContainer()->getParameter('binance_app_secret_key')
        );
        $this->getMqProducer()->publish(serialize($order));
        ######### AFTER RUN bin/console rabbitmq:consumer rule ##########
    }

}
