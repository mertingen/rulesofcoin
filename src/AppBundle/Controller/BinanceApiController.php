<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/15/18
 * Time: 11:19 PM
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Bid;
use AppBundle\Entity\Rule;
use AppBundle\Service\BinanceService;
use AppBundle\Service\UserBinanceService;
use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @Route("/rule/{symbol}", methods={"POST"}, name="binance-api-post-add-rule")
     * @param null $symbol
     * @param BinanceService $binanceService
     * @param Request $request
     * @param UserService $userService
     * @param UserBinanceService $userBinanceService
     * @return Response
     */
    public
    function postAddRuleAction($symbol = NULL, BinanceService $binanceService, Request $request, UserService $userService, UserBinanceService $userBinanceService)
    {
        $symbols = $this->get('redis_service')->get('symbols');
        if (!$symbols) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Coins are not take from Api!'
                ), 404
            );
        }
        if (!in_array($symbol, $symbols)) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Symbol is not valid!'
                ), 404
            );
        }

        $where = array(
            'user' => $this->getUser(),
            'symbol' => $symbol,
            'isDone' => false
        );
        $countUserRules = $binanceService->getCountUserRulesBySymbol($where);
        $symbolRuleCountLimit = $this->getParameter('rule_symbol_count_limit');
        if ($countUserRules >= $symbolRuleCountLimit) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'You can set max ' . $symbolRuleCountLimit . ' rules to a symbol!'
                ), 404
            );
        }

        $ruleLimit = $request->request->get('limit', null);
        $stop = $request->request->get('stop', null);
        $btcPrice = $request->request->get('btc-price', null);
        $quantity = $request->request->get('quantity', null);
        $stopType = $request->request->get('stop-type', null);
        $ruleType = $request->request->get('rule-type', null);
        $ruleParentId = $request->request->get('rule-parent-id', null);

        if (
            empty($ruleLimit) ||
            empty($btcPrice) ||
            empty($quantity) ||
            $quantity <= -1 ||
            empty($ruleType) ||
            !is_numeric($quantity) ||
            !is_numeric($ruleLimit) ||
            !is_numeric($btcPrice)
        ) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Parameters are not valid!'
                ), 404
            );
        }

        if ($ruleType != 'BUY' && $ruleType != 'SELL') {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule type is not found!'
                ), 404
            );
        }

        $btcPrice = $binanceService->getBtcNumberFormat(floatval($btcPrice));
        $ruleLimit = $binanceService->getBtcNumberFormat(floatval($ruleLimit));
        if (isset($stop) && $stop > 0) {
            $stop = $binanceService->getBtcNumberFormat(floatval($stop));
        }

        if ($btcPrice < 0.00010000) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Balance is not enough!'
                ), 404
            );
        }

        if (!empty($stop) && $stop > 0 && empty($stopType)) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Stop is not valid!'
                ), 404
            );
        }

        if ($stop > $ruleLimit) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Stop should greater than limit!'
                ), 404
            );
        }

        if ((strlen($ruleLimit) !== 10 && $ruleLimit < 0) || (!empty($stop) && (strlen($stop) !== 10) || $stop < 0)) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Limit is not valid!'
                ), 404
            );
        }

        $parentRule = NULL;
        if (!empty($ruleParentId)) {
            $parentRule = $binanceService->getRule(
                array(
                    'user' => $this->getUser(),
                    'id' => $ruleParentId
                )
            );
            if (!$parentRule) {
                return new JsonResponse(
                    array(
                        'error' => true, 'message' => 'Parent rule is not found!'
                    ), 404
                );
            }
        }

        $gainBtcPrice = 0;
        $gainQuantity = 0;
        if ($ruleType == 'BUY') {
            $validBtcPrice = $binanceService->getBtcNumberFormat(floatval($quantity * $ruleLimit));
            if ($btcPrice < $validBtcPrice) {
                return new JsonResponse(
                    array(
                        'error' => true, 'message' => 'Balance is not enough!'
                    ), 404
                );
            }
            if ($parentRule) {
                $gainQuantity = $quantity - $parentRule->getQuantity();
                $gainQuantity = (intval($gainQuantity) < 1) ? $binanceService->getBtcNumberFormat($gainQuantity) : intval($gainQuantity);
            } else {
                $gainQuantity = $quantity;
            }
        } elseif ($ruleType == 'SELL') {
            $userBinanceService->connect($this->getUser()->getBinanceApiKey(),
                $this->getUser()->getBinanceSecretKey());
            $validSymbolQuantity = intval($userBinanceService->getUserSymbolPrice($symbol));
            if ($validSymbolQuantity < $quantity) {
                //return new JsonJsonResponse(array('error' => true, 'message' => 'Balance is not enough!'));
                /*return new JsonResponse(
                    array(
                        'error' => true, 'message' => 'Balance is not enough!'
                    ), 404
                );*/
            }

            if ($parentRule) {
                $gainBtcPrice = $btcPrice - $parentRule->getBtcPrice();
                $gainBtcPrice = $binanceService->getBtcNumberFormat($gainBtcPrice);
            } else {
                $gainBtcPrice = $btcPrice;
            }
        }

        $user = $userService->get($this->getUser()->getId());

        $rule = new Rule();
        $rule->setUser($user);
        $rule->setSymbol($symbol);
        $rule->setCreatedAt(new \DateTime());
        $rule->setRuleLimit($ruleLimit);
        $rule->setStop($stop);
        $rule->setQuantity($quantity);
        $rule->setBtcPrice($btcPrice);
        $rule->setIsDone(false);
        $rule->setStopType($stopType);
        $rule->setType($ruleType);
        $rule->setParentRule($parentRule);
        $rule->setGainBtcPrice($gainBtcPrice);
        $rule->setGainQuantity($gainQuantity);

        $rule = $binanceService->upsertRule($rule);

        $binanceService->setRulesToRedis($user);

        return new JsonResponse(
            array(
                'error' => false, 'rule' => $rule, 'message' => 'Rule is successfully added.'
            ), 200
        );

    }

    /**
     * @Route("/editRule/{id}", methods={"POST"}, name="binance-api-post-edit-rule")
     * @param Rule|null $rule
     * @param BinanceService $binanceService
     * @param Request $request
     * @param UserService $userService
     * @param UserBinanceService $userBinanceService
     * @return Response
     */
    public
    function postEditRuleAction(Rule $rule = NULL, BinanceService $binanceService, Request $request, UserService $userService, UserBinanceService $userBinanceService)
    {
        if (!$rule) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule is not found!'
                ), 404
            );
        }

        $isUserRule = $binanceService->getRule(array('id' => $rule->getId(), 'user' => $this->getUser()));
        if (!$isUserRule) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule is not found!'
                ), 404
            );
        }

        $symbols = $this->get('redis_service')->get('symbols');
        if (!$symbols) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Coins are not take from Api!'
                ), 404
            );
        }
        if (!in_array($rule->getSymbol(), $symbols)) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Symbol is not valid!'
                ), 404
            );
        }

        $where = array(
            'user' => $this->getUser(),
            'symbol' => $rule->getSymbol(),
            'isDone' => false
        );
        $countUserRules = $binanceService->getCountUserRulesBySymbol($where);
        $symbolRuleCountLimit = $this->getParameter('rule_symbol_count_limit');
        if ($countUserRules >= $symbolRuleCountLimit) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'You can set max ' . $symbolRuleCountLimit . ' rules to a symbol!'
                ), 404
            );
        }

        $ruleLimit = $request->request->get('limit', null);
        $stop = $request->request->get('stop', null);
        $btcPrice = $request->request->get('btc-price', null);
        $quantity = $request->request->get('quantity', null);
        $stopType = $request->request->get('stop-type', null);
        $ruleType = $request->request->get('rule-type', null);
        $ruleParentId = $request->request->get('rule-parent-id', null);

        if (
            empty($ruleLimit) ||
            empty($btcPrice) ||
            empty($quantity) ||
            $quantity <= -1 ||
            empty($ruleType) ||
            !is_numeric($quantity) ||
            !is_numeric($ruleLimit) ||
            !is_numeric($btcPrice)
        ) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Parameters are not valid!'
                ), 404
            );
        }

        if ($ruleType != 'BUY' && $ruleType != 'SELL') {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule type is not found!'
                ), 404
            );
        }

        $btcPrice = $binanceService->getBtcNumberFormat(floatval($btcPrice));
        $ruleLimit = $binanceService->getBtcNumberFormat(floatval($ruleLimit));
        if (isset($stop) && $stop > 0) {
            $stop = $binanceService->getBtcNumberFormat(floatval($stop));
        }

        if ($btcPrice < 0.00010000) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Balance is not enough!'
                ), 404
            );
        }

        if (!empty($stop) && $stop > 0 && empty($stopType)) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Stop is not valid!'
                ), 404
            );
        }

        if ($stop > $ruleLimit) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Stop should greater than limit!'
                ), 404
            );
        }

        if ((strlen($ruleLimit) !== 10 && $ruleLimit < 0) || (!empty($stop) && (strlen($stop) !== 10) || $stop < 0)) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Limit is not valid!'
                ), 404
            );
        }

        $parentRule = NULL;
        if (!empty($ruleParentId)) {
            $parentRule = $binanceService->getRule(
                array(
                    'user' => $this->getUser(),
                    'id' => $ruleParentId
                )
            );
            if (!$parentRule) {
                return new JsonResponse(
                    array(
                        'error' => true, 'message' => 'Parent rule is not found!'
                    ), 404
                );
            }
        }

        $gainBtcPrice = 0;
        $gainQuantity = 0;
        if ($ruleType == 'BUY') {
            $validBtcPrice = $binanceService->getBtcNumberFormat(floatval($quantity * $ruleLimit));
            if ($btcPrice < $validBtcPrice) {
                return new JsonResponse(
                    array(
                        'error' => true, 'message' => 'Balance is not enough!'
                    ), 404
                );
            }
            if ($parentRule) {
                $gainQuantity = $quantity - $parentRule->getQuantity();
                $gainQuantity = (intval($gainQuantity) < 1) ? $binanceService->getBtcNumberFormat($gainQuantity) : intval($gainQuantity);
            } else {
                $gainQuantity = $quantity;
            }
        } elseif ($ruleType == 'SELL') {
            $userBinanceService->connect($this->getUser()->getBinanceApiKey(),
                $this->getUser()->getBinanceSecretKey());
            $validSymbolQuantity = intval($userBinanceService->getUserSymbolPrice($rule->getSymbol()));
            if ($validSymbolQuantity < $quantity) {
                //return new JsonJsonResponse(array('error' => true, 'message' => 'Balance is not enough!'));
                /*return new JsonResponse(
                    array(
                        'error' => true, 'message' => 'Balance is not enough!'
                    ), 404
                );*/
            }

            if ($parentRule) {
                $gainBtcPrice = $btcPrice - $parentRule->getBtcPrice();
                $gainBtcPrice = $binanceService->getBtcNumberFormat($gainBtcPrice);
            } else {
                $gainBtcPrice = $btcPrice;
            }
        }

        $user = $userService->get($this->getUser()->getId());

        $rule->setRuleLimit($ruleLimit);
        $rule->setStop($stop);
        $rule->setQuantity($quantity);
        $rule->setBtcPrice($btcPrice);
        $rule->setStopType($stopType);
        $rule->setParentRule($parentRule);
        $rule->setGainBtcPrice($gainBtcPrice);
        $rule->setGainQuantity($gainQuantity);

        $rule = $binanceService->upsertRule($rule);

        $binanceService->setRulesToRedis($user);

        return new JsonResponse(
            array(
                'error' => false, 'rule' => $rule, 'message' => 'Rule is successfully updated.'
            ), 200
        );

    }


    /**
     * @param UserBinanceService $userBinanceService
     * @return Response
     * @Route("/user-btc-price", name="binance-api-user-btc-price")
     */
    public function getUserBtcPriceAction(UserBinanceService $userBinanceService)
    {
        $binanceApiKey = $this->getUser()->getBinanceApiKey();
        $binanceSecretKey = $this->getUser()->getBinanceSecretKey();
        $userBinanceService->connect($binanceApiKey, $binanceSecretKey);
        $userBtc = $userBinanceService->getUserBtcPrice();

        return new JsonResponse(
            array(
                'error' => false, 'userBtc' => $userBtc
            ), 200
        );
    }

    /**
     * @Route("/user-btc-to-usd", name="binance-api-user-btc-to-usd")
     */
    public function getUserBtcToUsdAction()
    {
        $result = json_decode(file_get_contents("https://www.bitstamp.net/api/ticker/"));
        return new JsonResponse(
            array(
                'error' => false, 'usd' => $result->open
            ), 200
        );
    }

    /**
     * @Route("/bid/{id}", methods={"GET"}, name="binance-api-get-bid")
     * @param Bid|null $bid
     * @param BinanceService $binanceService
     * @return Response
     */
    public function getBid(Bid $bid = NULL, BinanceService $binanceService)
    {
        if (!$bid) {
            return new JsonResponse(
                array(
                    'error' => false, 'message' => 'Order not found!'
                ), 404
            );
        }

        $rule = $binanceService->getRule(array('id' => $bid->getRule()->getId(), 'user' => $this->getUser()));
        if ($rule) {
            return new JsonResponse(
                array(
                    'error' => false, 'bid' => $bid
                ), 200
            );
        } else {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Order not found!'
                ), 404
            );
        }
    }

    /**
     * @Route("/rule/{id}", methods={"GET"}, name="binance-api-get-rule")
     * @param Rule $rule
     * @param BinanceService $binanceService
     * @return Response
     */
    public function getRule(Rule $rule = NULL, BinanceService $binanceService)
    {
        if (!$rule) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule not found!'
                ), 404
            );
        }

        $rule = $binanceService->getRule(array('id' => $rule->getId(), 'user' => $this->getUser()));
        if ($rule) {
            return new JsonResponse(
                array(
                    'error' => false, 'rule' => $rule
                ), 200
            );
        } else {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule not found!'
                ), 404
            );
        }
    }

    /**
     * @Route("/removeRuleWithChildren/{id}", methods={"GET"}, name="binance-api-remove-rule-wtih-children")
     * @param Rule $rule
     * @param BinanceService $binanceService
     * @return Response
     */
    public function removeRuleWithChildren(Rule $rule = NULL, BinanceService $binanceService)
    {
        if (!$rule) {
            return new JsonResponse(
                array(
                    'error' => true, 'message' => 'Rule not found!'
                ), 404
            );
        }

        $removingRule = $rule;

        $removedIds = array();
        $removedIds[] = $rule->getId();
        do {
            $childRule = $binanceService->getRule(
                array(
                    'user' => $this->getUser(),
                    'parentRule' => $rule
                )
            );
            if ($childRule) {
                $removedIds[] = $childRule->getId();
                $rule = $childRule;
            }
        } while ($childRule);

        $responseArray = array(
            'error' => false,
            'ids' => $removedIds,
            'message' => 'Rule is successfully removed!',
            'parentRuleId' => null
        );

        $haveParentOrChildRule = $binanceService->checkRuleParentOrChild($removingRule);
        if ($haveParentOrChildRule) {
            $parentRule = $removingRule->getParentRule();
            if ($parentRule) {
                $grandParent = $removingRule->getParentRule()->getParentRule();
                $responseArray['parentRuleId'] = ($grandParent) ? null : $removingRule->getParentRule()->getId();
            }
        }

        $binanceService->removeRule($removingRule);
        return new JsonResponse(
            $responseArray
            , 200
        );
    }
}