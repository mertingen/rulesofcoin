<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/15/18
 * Time: 11:19 PM
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Bid;
use AppBundle\Service\BinanceService;
use AppBundle\Service\UserBinanceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class BinanceApiController
 * @package AppBundle\Controller
 * @Route("/binance-api")
 * @Security("has_role('ROLE_USER')")
 */
class BinanceApiController extends Controller
{

    /**
     * @param UserBinanceService $userBinanceService
     * @return JsonResponse
     * @Route("/user-btc-price", name="binance-api-user-btc-price")
     */
    public function getUserBtcPriceAction(UserBinanceService $userBinanceService)
    {
        $binanceApiKey = $this->getUser()->getBinanceApiKey();
        $binanceSecretKey = $this->getUser()->getBinanceSecretKey();
        $userBinanceService->connect($binanceApiKey, $binanceSecretKey);
        $userBtc = $userBinanceService->getUserBtcPrice();

        return new JsonResponse(array('userBtc' => $userBtc));
    }

    /**
     * @Route("/user-btc-to-usd", name="binance-api-user-btc-to-usd")
     */
    public function getUserBtcToUsdAction()
    {
        $result = json_decode(file_get_contents("https://www.bitstamp.net/api/ticker/"));
        return new JsonResponse(array('usd' => $result->open));
    }

    /**
     * @Route("/bid/{id}", methods={"GET"}, name="binance-api-get-bid")
     * @param Bid|null $bid
     * @param BinanceService $binanceService
     * @return JsonResponse
     */
    public function getBid(Bid $bid = NULL, BinanceService $binanceService)
    {
        if (!$bid) {
            return new JsonResponse(array('error' => true, 'message' => 'Order not found!'));
        }

        $rule = $binanceService->getRule(array('id' => $bid->getRule()->getId(), 'user' => $this->getUser()));
        if ($rule){
            return new JsonResponse(
                array(
                    'executedQuantity' => $bid->getExecutedQuantity(),
                    'date' => $bid->getCreatedAt()->format('d.m.Y H:i:s'),
                    'status' => $bid->getStatus(),
                    'orderId' => $bid->getOrderId()
                )
            );
        }
    }
}