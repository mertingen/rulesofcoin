<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/19/18
 * Time: 11:28 PM
 */

namespace AppBundle\Service;


use Binance\API;

class UserBinanceService
{
    /**
     * @var API $api
     */
    private $api;

    /**
     * BinanceService constructor.
     * @param API $binanceApi
     */
    public function __construct($binanceApi)
    {
        $this->api = $binanceApi;
    }

    public function connect($binanceApiKey = NULL, $binanceSecretKey = NULL)
    {
        $this->api = new $this->api($binanceApiKey, $binanceSecretKey);
    }

    /**
     * @return mixed
     */
    public function getUserBtcPrice()
    {
        $btcAvailable = $this->api->balances()['BTC']['available'];
        return $btcAvailable;
    }

    /**
     * @param int $price
     * @param string $symbol
     * @return array
     */
    public function getSymbolQuantityByBtc($price = 0, $symbol = '')
    {
        $btcAvailable = $this->api->balances()['BTC']['available'];
        $quantity = intval($btcAvailable / $price);
        return $quantity;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function buy($data = array())
    {
        return $this->api->buy($data['symbol'], $data['quantity'], $data['limit']);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getOrderStatus($data = array())
    {
        return $this->api->orderStatus($data['symbol'], $data['orderId']);
    }

}