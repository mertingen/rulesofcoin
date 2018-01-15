<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/15/18
 * Time: 11:19 PM
 */

namespace AppBundle\Controller;


use AppBundle\Service\BinanceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class BinanceApiController
 * @package AppBundle\Controller
 * @Route("/binance-api/")
 * @Security("has_role('ROLE_USER')")
 */
class BinanceApiController extends Controller
{
    /**
     * @param BinanceService $binanceService
     * @Route("/user-btc-price", name="binance-api-user-btc-price")
     * @return JsonResponse
     */
    public function getUserBtcPriceAction(BinanceService $binanceService)
    {
        $userBtc = $binanceService->getUserBtcPrice($this->getUser());
        return new JsonResponse(array('userBtc' => $userBtc));
    }

    /**
     * @Route("user-btc-to-usd", name="binance-api-user-btc-to-usd")
     */
    public function getUserBtcToUsdAction()
    {
        $result = json_decode(file_get_contents("https://www.bitstamp.net/api/ticker/"));
        return new JsonResponse(array('usd' => $result->open));

    }


}