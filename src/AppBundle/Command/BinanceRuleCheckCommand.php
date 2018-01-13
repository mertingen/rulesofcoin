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
                        if ($trades['buyLimit'] <= $symbolRule['price']) {
                            if (isset($symbolRule['stop']) && is_numeric($symbolRule['stop'])) {
                                $isStop = true;
                                $this->buy($symbolRule, $symbol, $trades, $isStop);
                                echo PHP_EOL . '[' . $symbol . ']' . ' için  STOP-LIMIT emir girildi! RULE: ' . $symbolRule['stop'] . ' PRICE:' . $trades['price'] . PHP_EOL;
                            } else {
                                $this->buy($symbolRule, $symbol, $trades);
                                echo PHP_EOL . '[' . $symbol . ']' . ' için  LIMIT emir girildi! RULE: ' . $symbolRule['buyLimit'] . ' PRICE:' . $trades['price'] . PHP_EOL;
                            }
                            unset($rules[$symbolKey]);
                            $this->getRedisService()->insert('rules', $rules);
                        } else {
                            echo '[' . $symbol . ']' . ' RULE İŞLENMEDİ' . ' RULE: ' . $symbolRule['buyLimit'] . ' PRICE:' . $trades['price'] . PHP_EOL;
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
        return $this->getContainer()->get('old_sound_rabbit_mq.rule_consumer');
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
     * @param bool $isStop
     */
    public function buy($rule = array(), $symbol = '', $trades = array(), $isStop = false)
    {
        $userBinanceApi = new API(
            $rule['binance_api_key'],
            $rule['binance_secret_key']
        );
        //$btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        $quantity = intval($rule['btcPrice'] / $trades['price']);
        if ($isStop) {
            $result = $userBinanceApi->buy($symbol, $quantity, $rule['stop']);
        } else {
            $result = $userBinanceApi->buy($symbol, $quantity, $trades['price']);
        }
        if (is_array($result)) {
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
