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
                    foreach ($rules[$symbol] as &$symbolRule) {
                        if ($trades['buyLimit'] <= $symbolRule['price']) {
                            $this->buy($symbolRule, $symbol, $trades);
                            echo PHP_EOL . '[' . $symbol . ']' . ' için emir girildi! RULE: ' . $symbolRule['buyLimit'] . ' PRICE:' . $trades['price'] . PHP_EOL;

                        } elseif (isset($symbolRule['stop']) && is_numeric($symbolRule['stop'])) {
                            if ($trades['stop'] < $trades['price']) {
                                if (!array_key_exists('activeStop', $symbolRule)) {
                                    $symbolRule['activeStop'] = true;
                                }
                            } elseif ((array_key_exists('activeStop', $symbolRule) && $symbolRule['activeStop']) && $symbolRule['stop'] == $trades['price']) {
                                $this->buy($symbolRule, $symbol, $trades);
                            }
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
     */
    public function buy($rule = array(), $symbol = '', $trades = array())
    {
        $userBinanceApi = new API(
            $rule['binance_api_key'],
            $rule['binance_secret_key']
        );
        $btcAvailable = $userBinanceApi->balances()['BTC']['available'];
        $quantity = intval($btcAvailable / $trades['price']);
        $userBinanceApi->buy($symbol, $quantity, $trades['price']);
    }

}
