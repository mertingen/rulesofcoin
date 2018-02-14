<?php

namespace AppBundle\Command;

use AppBundle\Entity\Rule;
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
                if (!empty($rules)) {
                    if (isset($rules[$symbol])) {
                        /**
                         * @var Rule $symbolRule
                         */
                        foreach ($rules[$symbol] as $ruleId => $symbolRule) {
                            $quantity = intval($symbolRule['quantity']);
                            if (isset($symbolRule['stop']) && is_numeric($symbolRule['stop']) && $symbolRule['stop'] > 0 && isset($symbolRule['stopType'])) {
                                if ($symbolRule['stopType'] == 'smaller' && $trades['price'] <= $symbolRule['stop']) {
                                    unset($rules[$symbol][$ruleId]);
                                    $this->getRedisService()->insert('rules', $rules);
                                    if ($symbolRule->getType() == 'BUY') {
                                        $this->buy($symbolRule, $symbol, $quantity);
                                    } elseif ($symbolRule->getType() == 'SELL') {
                                        $this->sell($symbolRule, $symbol, $quantity);
                                    }
                                    if ($symbolRule->getParentRule()) {
                                        $keys = array(
                                            'binance_api_key' => $symbolRule['binance_api_key'],
                                            'binance_secret_key' => $symbolRule['binance_secret_key']
                                        );
                                        $this->setRuleToRedis($symbolRule->getParentRule(), $rules, $keys, $symbol);
                                    }
                                    echo '[' . $symbolRule->getType() . ' / ' . $symbol . ']' . ' - ' . '[STOP-LIMIT-SMALLER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['ruleLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . '[DATE:' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
                                } elseif ($symbolRule['stopType'] == 'greater' && $trades['price'] >= $symbolRule['stop']) {
                                    unset($rules[$symbol][$ruleId]);
                                    $this->getRedisService()->insert('rules', $rules);
                                    if ($symbolRule->getType() == 'BUY') {
                                        $this->buy($symbolRule, $symbol, $quantity);
                                    } elseif ($symbolRule->getType() == 'SELL') {
                                        $this->sell($symbolRule, $symbol, $quantity);
                                    }
                                    if ($symbolRule->getParentRule()) {
                                        $keys = array(
                                            'binance_api_key' => $symbolRule['binance_api_key'],
                                            'binance_secret_key' => $symbolRule['binance_secret_key']
                                        );
                                        $this->setRuleToRedis($symbolRule->getParentRule(), $rules, $keys, $symbol);
                                    }
                                    echo '[' . $symbolRule->getType() . ' / ' . $symbol . ']' . ' - ' . '[STOP-LIMIT-SMALLER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['ruleLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . '[DATE:' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
                                }
                            } else {
                                if ($trades['price'] <= $symbolRule['ruleLimit']) {
                                    unset($rules[$symbol][$ruleId]);
                                    $this->getRedisService()->insert('rules', $rules);
                                    if ($symbolRule->getType() == 'BUY') {
                                        $this->buy($symbolRule, $symbol, $quantity);
                                    } elseif ($symbolRule->getType() == 'SELL') {
                                        $this->sell($symbolRule, $symbol, $quantity);
                                    }
                                    if ($symbolRule->getParentRule()) {
                                        $keys = array(
                                            'binance_api_key' => $symbolRule['binance_api_key'],
                                            'binance_secret_key' => $symbolRule['binance_secret_key']
                                        );
                                        $this->setRuleToRedis($symbolRule->getParentRule(), $rules, $keys, $symbol);
                                    }
                                    echo '[' . $symbolRule->getType() . ' / ' . $symbol . ']' . ' - ' . '[STOP-LIMIT-SMALLER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['ruleLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . '[DATE:' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
                                }
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
            'limit' => $rule['ruleLimit']
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
            'limit' => $rule['ruleLimit']
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

    /**
     * @param Rule $rule
     * @param array $rules
     * @param array $keys
     * @param $symbol
     */
    public function setRuleToRedis(Rule $rule, $rules = array(), $keys = array(), $symbol)
    {
        $rules[$symbol][$rule->getId()] = array(
            'ruleId' => $rule->getId(),
            'ruleLimit' => $rule->getRuleLimit(),
            'stop' => $rule->getStop(),
            'binance_api_key' => $keys['binance_api_key'],
            'binance_secret_key' => $keys['binance_secret_key'],
            'btcPrice' => $rule->getBtcPrice(),
            'quantity' => $rule->getQuantity(),
            'stopType' => $rule->getStopType(),
            'type' => $rule->getType()
        );
        $this->getRedisService()->insert('rules', $rules);
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
