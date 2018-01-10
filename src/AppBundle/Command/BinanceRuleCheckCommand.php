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
                    foreach ($rules[$symbol] as $symbolRules) {
                        if ($trades['price'] <= $symbolRules['price']) {
                            $userBinanceApi = new API(
                                $symbolRules['binance_api_key'],
                                $symbolRules['binance_secret_key']
                            );
                            $btcAvailable = $userBinanceApi->balances()['BTC']['available'];
                            $quantity = intval($btcAvailable / $trades['price']);
                            $userBinanceApi->buy($symbol, $quantity, $trades['price']);
                            echo PHP_EOL . $symbol . ' için emir girildi! RULE: ' . $symbolRules['price'] . ' PRICE:' . $trades['price'] . PHP_EOL;
                        } else {
                            echo '[' . $symbol . ']' . ' RULE İŞLENMEDİ' . ' RULE: ' . $symbolRules['price'] . ' PRICE:' . $trades['price'] . PHP_EOL;
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

}
