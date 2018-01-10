<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/8/18
 * Time: 12:57 PM
 */

namespace AppBundle\Consumer;


use AppBundle\Service\RedisService;
use Binance\API;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RuleConsumer implements ConsumerInterface
{
    private $binanceApi;
    private $rule;
    private $redisService;

    /**
     * RuleConsumer constructor.
     * @param $binanceApiKey
     * @param $binanceSecretKey
     * @param RedisService $redisService
     */
    public function __construct($binanceApiKey, $binanceSecretKey, RedisService $redisService)
    {
        $this->binanceApi = new API($binanceApiKey, $binanceSecretKey);
        $this->redisService = $redisService;
    }

    public function execute(AMQPMessage $data)
    {
        /*try {
            $rule = unserialize($data->body);
            $this->setRules($rule),
            $rules = $this->getRules();
            $symbols = $this->getSymbols();
            $this->binanceApi->trades($symbols, function ($api, $symbol, $trades) {
                $rules = $this->getRules();
                if ($trades['price'] <= $rule['price']) {
                    //echo 'SUCCESS! Rule price -> ' . $rule['price'] . ' Trade price -> ' . $trades['price'];
                } else {
                    //echo 'NOW PRICE -> ' . $trades['price'] . PHP_EOL;
                }
            });
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
        }*/
    }

    /**
     * @return mixed
     */
    public function getSymbols()
    {
        return $this->redisService->get('symbols');
    }

    /**
     * @return mixed
     */
    public function getRules()
    {
        return $this->redisService->get('rules');
    }

    /**
     * @param $rule
     */
    public function setRules($rule)
    {
        /*$rules = $this->getRules();
        if (is_array($rules)) {
            $data = array();
            foreach ($rules as $symbol => $ruleData) {
                $data[$symbol][] = array(
                    'ruleId' => $rule['ruleId'],
                    'price' => $rule['price']
                );
            }
            $this->redisService->insert('rules', $rules);
        }*/
    }
}