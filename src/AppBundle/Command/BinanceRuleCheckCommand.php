<?php

namespace AppBundle\Command;

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
                    foreach ($rules[$symbol] as $symbolKey => &$symbolRule) {
                        $quantity = intval($symbolRule['quantity']);
                        if (isset($symbolRule['stop']) && is_numeric($symbolRule['stop']) && $symbolRule['stop'] > 0 && isset($symbolRule['stopType'])) {
                            if ($symbolRule['stopType'] == 'smaller' && $trades['price'] <= $symbolRule['stop']) {
                                unset($rules[$symbolKey]);
                                $this->getRedisService()->insert('rules', $rules);
                                $this->buy($symbolRule, $symbol, $trades, $quantity);
                                echo '[' . $symbol . ']' . ' - ' . '[STOP-LIMIT-SMALLER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['buyLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . PHP_EOL;
                            } elseif ($symbolRule['stopType'] == 'greater' && $trades['price'] >= $symbolRule['stop']) {
                                unset($rules[$symbolKey]);
                                $this->getRedisService()->insert('rules', $rules);
                                $this->buy($symbolRule, $symbol, $trades, $quantity);
                                echo '[' . $symbol . ']' . ' - ' . '[STOP-LIMIT-GREATER]' . ' - ' . '[QUANTITY:' . $quantity . ']' . ' - ' . '[STOP:' . $symbolRule['stop'] . ']' . '[LIMIT:' . $symbolRule['buyLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . PHP_EOL;
                            }
                        } else {
                            if ($trades['price'] <= $symbolRule['buyLimit']) {
                                unset($rules[$symbolKey]);
                                $this->getRedisService()->insert('rules', $rules);
                                $this->buy($symbolRule, $symbol, $trades, $quantity);
                                echo '[' . $symbol . ']' . ' - ' . '[LIMIT]' . ' - ' . '[' . $quantity . ']' . ' - ' . '[LIMIT:' . $symbolRule['buyLimit'] . ']' . ' - ' . '[PRICE:' . $trades['price'] . ']' . PHP_EOL;
                            }
                        }
                    }
                }
            });
        } else {
            $output->write('Rules not found!!!');
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

    /**
     * @return mixed
     */
    public function getSymbols()
    {
        return $this->getRedisService()->get('symbols');
    }


    /**
     * @param array $rule
     * @param string $symbol
     * @param array $trades
     * @param int $quantity
     * @param bool $isStop
     */
    public function buy($rule = array(), $symbol = '', $trades = array(), $quantity = 0, $isStop = false)
    {
        $userBinanceApi = new API(
            $rule['binance_api_key'],
            $rule['binance_secret_key']
        );
        //$btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        //$quantity = intval($rule['btcPrice'] / $trades['price']);
        $result = $userBinanceApi->buy($symbol, $quantity, $rule['buyLimit']);
        if (is_array($result)) {
            if (array_key_exists('code', $result) && is_numeric($result['code'])) {
                echo '[ERROR] -> ' . $result['msg'] . PHP_EOL;
            } else {
                $order = array(
                    $rule['ruleId'] = array(
                        'orderId' => $result['orderId'],
                        'clientOrderId' => $result['clientOrderId'],
                        'createdAt' => new \DateTime()
                    )
                );
                $this->getMqProducer()->publish(serialize($order));
            }
        }
    }

}
