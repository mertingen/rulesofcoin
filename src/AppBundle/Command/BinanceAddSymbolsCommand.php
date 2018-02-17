<?php

namespace AppBundle\Command;

use Binance\API;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BinanceAddSymbolsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('binance:add:symbols')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api = $this->getBinanceApi();
        $symbols = array();
        foreach ($api->prices() as $symbol => $price) {
            $lowerSymbol = strtolower($symbol);
            if (strpos($lowerSymbol, "btc") > -1 && $lowerSymbol != 'btcusdt') {
                echo $symbol . ' added...' . PHP_EOL;
                $symbols[] = $symbol;
            }
        }
        $this->getRedisService()->insert('symbols', $symbols);

        $output->writeln('Binance symbols inserted to Redis!');
    }

    /**
     * @return API
     */
    public function getBinanceApi()
    {
        return new API(
            $this->getContainer()->getParameter('binance_app_api_key'),
            $this->getContainer()->getParameter('binance_app_secret_key')
        );
    }

    /**
     * @return \AppBundle\Service\RedisService|mixed|object
     */
    public function getRedisService()
    {
        return $this->getContainer()->get('redis_service');
    }

}
