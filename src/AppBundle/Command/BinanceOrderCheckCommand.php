<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/14/18
 * Time: 12:01 PM
 */

namespace AppBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BinanceOrderCheckCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('binance:order:check')
            ->setDescription("Check the user's orders.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $orders = $entityManager->getRepository('AppBundle:Bid')->findBy(array('status' => 'NEW'));
        if ($orders) {
            foreach ($orders as $order) {
                $this->getContainer()->get('old_sound_rabbit_mq.rule_producer')->publish(serialize($order));
            }
        }

    }

}