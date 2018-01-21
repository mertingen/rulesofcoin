<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Rule;
use AppBundle\Entity\User;
use AppBundle\Service\BinanceService;
use AppBundle\Service\UserBinanceService;
use AppBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class SettingController
 * @package AppBundle\Controller
 * @Route("/binance")
 * @Security("has_role('ROLE_USER')")
 */
class BinanceController extends Controller
{
    private $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /**
     * @Route("/coins", methods={"GET"}, name="binance-coin-list")
     * @param BinanceService $binanceService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function coinsAction(BinanceService $binanceService)
    {
        $coins = $binanceService->getCoinsWithPrices();
        return $this->render('@App/Binance/Coin/list-coin.html.twig',
            array('coins' => $coins)
        );
    }

    /**
     * @Route("/rule/{symbol}", methods={"GET"}, name="binance-add-rule")
     * @param null $symbol
     * @param BinanceService $binanceService
     * @param UserBinanceService $userBinanceService
     * @param FlashBagInterface $flashBag
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function addRuleAction($symbol = NULL, BinanceService $binanceService, UserBinanceService $userBinanceService, FlashBagInterface $flashBag)
    {
        $symbols = $this->get('redis_service')->get('symbols');
        if (!in_array($symbol, $symbols)) {
            $this->flashBag->add('error', 'Symbol is not valid!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $where = array(
            'user' => $this->getUser(),
            'symbol' => $symbol,
            'isDone' => false
        );
        $countUserRules = $binanceService->getCountUserRulesBySymbol($where);
        if ($countUserRules >= $this->getParameter('rule_symbol_count_limit')) {
            $this->flashBag->add('error', 'You can set max 4 rules to a symbol!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $data = $binanceService->getCoinsWithPrices($symbol);
        $binanceApiKey = $this->getUser()->getBinanceApiKey();
        $binanceSecretKey = $this->getUser()->getBinanceSecretKey();
        $userBinanceService->connect($binanceApiKey, $binanceSecretKey);
        $data['quantity'] = $userBinanceService->getSymbolQuantityByBtc($data['price'], $data['symbol']);
        $data['btcPrice'] = $userBinanceService->getUserBtcPrice();


        return $this->render('@App/Binance/Rule/add-rule.html.twig',
            array(
                'data' => $data
            )
        );
    }

    /**
     * @Route("/rule/{symbol}", methods={"POST"}, name="binance-post-add-rule")
     * @param null $symbol
     * @param BinanceService $binanceService
     * @param Request $request
     * @param UserService $userService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public
    function postAddRuleAction($symbol = NULL, BinanceService $binanceService, Request $request, UserService $userService)
    {
        $symbols = $this->get('redis_service')->get('symbols');

        if (!in_array($symbol, $symbols)) {
            $this->flashBag->add('error', 'Symbol is not valid!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $where = array(
            'user' => $this->getUser(),
            'symbol' => $symbol,
            'isDone' => false
        );
        $countUserRules = $binanceService->getCountUserRulesBySymbol($where);
        if ($countUserRules >= $this->getParameter('rule_symbol_count_limit')) {
            $this->flashBag->add('error', 'You can set max 4 rules to a symbol!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $buyLimit = $request->request->get('buy-limit', null);
        $stop = $request->request->get('stop', null);
        $btcPrice = $request->request->get('btc-price', null);
        $quantity = $request->request->get('quantity', null);
        $stopType = $request->request->get('stop-type', null);

        if (empty($buyLimit)) {
            $this->flashBag->add('error', 'Buy limit is not found!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }

        if (empty($btcPrice)) {
            $this->flashBag->add('error', 'Btc price not found!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }


        if (empty($quantity) && !is_numeric($quantity) && $quantity > 0) {
            $this->flashBag->add('error', 'Quantity is not valid!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }

        $btcPrice = $binanceService->getBtcNumberFormat(floatval($btcPrice));
        $buyLimit = $binanceService->getBtcNumberFormat(floatval($buyLimit));
        if (isset($stop) && $stop > 0) {
            $stop = $binanceService->getBtcNumberFormat(floatval($stop));
        }

        if ($btcPrice < 0.00010000) {
            $this->flashBag->add('error', 'Btc Price should bigger 0.0001');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }

        $isValidBtcPrice = $binanceService->getBtcNumberFormat(floatval($quantity * $buyLimit));
        if ($btcPrice < $isValidBtcPrice) {
            $this->flashBag->add('error', 'Btc price not valid!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }


        if (!empty($stop) && $stop > 0 && empty($stopType)) {
            $this->flashBag->add('error', 'Stop is not valid!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }

        if ($stop > $buyLimit) {
            $this->flashBag->add('error', 'Stop should greater than buy limit!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }

        if ((strlen($buyLimit) !== 10 && $buyLimit < 0) || (!empty($stop) && (strlen($stop) !== 10) || $stop < 0)) {
            $this->flashBag->add('error', 'Buy limit or Stop is not valid!');
            return $this->redirectToRoute('binance-add-rule', array('symbol' => $symbol));
        }


        $user = $userService->get($this->getUser()->getId());

        $rule = new Rule();
        $rule->setUser($user);
        $rule->setSymbol($symbol);
        $rule->setCreatedAt(new \DateTime());
        $rule->setBuyLimit($buyLimit);
        $rule->setStop($stop);
        $rule->setQuantity($quantity);
        $rule->setBtcPrice($btcPrice);
        $rule->setIsDone(false);
        $rule->setStopType($stopType);

        $ruleId = $binanceService->upsertRule($rule)->getId();

        $binanceService->setRulesToRedis($user);

        $this->flashBag->add('success', 'Rule is successfuly inserted!');
        return $this->redirectToRoute('binance-rule-list', array('id' => $user->getId()));

    }

    /**
     * @Route("/editRule/{id}", methods={"GET"}, name="binance-edit-rule")
     * @param null $id
     * @param BinanceService $binanceService
     * @param UserBinanceService $userBinanceService
     * @return Response
     */
    public
    function editRuleAction($id = NULL, BinanceService $binanceService, UserBinanceService $userBinanceService)
    {
        if (!$id) {
            $this->flashBag->add('error', 'Rule not found!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $rule = $binanceService->getRule(array('id' => $id, 'user' => $this->getUser()));
        if (!$rule) {
            $this->flashBag->add('error', 'Rule is not found!');
            return $this->redirectToRoute('binance-add-rule');
        }

        $data = $binanceService->getCoinsWithPrices($rule->getSymbol());
        $binanceApiKey = $this->getUser()->getBinanceApiKey();
        $binanceSecretKey = $this->getUser()->getBinanceSecretKey();
        $userBinanceService->connect($binanceApiKey, $binanceSecretKey);
        $data['btcPrice'] = $userBinanceService->getUserBtcPrice();

        return $this->render('@App/Binance/Rule/edit-rule.html.twig',
            array(
                'rule' => $rule,
                'data' => $data
            )
        );
    }

    /**
     * @Route("/editRule/{id}", methods={"POST"}, name="binance-post-edit-rule")
     * @param null $id
     * @param BinanceService $binanceService
     * @param Request $request
     * @param UserService $userService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public
    function postEditRuleAction($id = NULL, BinanceService $binanceService, Request $request, UserService $userService)
    {
        if (!$id) {
            $this->flashBag->add('error', 'Rule is not found!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $rule = $binanceService->getRule(array('id' => $id, 'user' => $this->getUser()));
        if (!$rule) {
            $this->flashBag->add('error', 'Rule is not found!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $buyLimit = $request->request->get('buy-limit', null);
        $btcPrice = $request->request->get('btc-price', null);
        $quantity = $request->request->get('quantity', null);
        $stopType = $request->request->get('stop-type', null);
        $stop = $request->request->get('stop', null);

        if (!$buyLimit) {
            $this->flashBag->add('error', 'Limit is not found!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }

        if (empty($btcPrice)) {
            $this->flashBag->add('error', 'Btc price not found!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }


        if (empty($quantity) && !is_numeric($quantity) && $quantity > 0) {
            $this->flashBag->add('error', 'Quantity is not valid!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }


        $btcPrice = $binanceService->getBtcNumberFormat(floatval($btcPrice));
        $buyLimit = $binanceService->getBtcNumberFormat(floatval($buyLimit));
        if (isset($stop) && $stop > 0) {
            $stop = $binanceService->getBtcNumberFormat(floatval($stop));
        }

        $isValidBtcPrice = $binanceService->getBtcNumberFormat(floatval($quantity * $buyLimit));

        if ($btcPrice < 0.00010000) {
            $this->flashBag->add('error', 'Btc Price should bigger 0.0001');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }

        if ($btcPrice < $isValidBtcPrice) {
            $this->flashBag->add('error', 'Btc price not valid!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }

        if (!empty($stop) && $stop > 0 && empty($stopType)) {
            $this->flashBag->add('error', 'Stop is not valid!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }

        if ($stop > $buyLimit) {
            $this->flashBag->add('error', 'Stop should greater than buy limit!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }

        if ((strlen($buyLimit) !== 10 && $buyLimit < 0) || (!empty($stop) && (strlen($stop) !== 10) || $stop < 0)) {
            $this->flashBag->add('error', 'Buy Limit or Stop is not valid!');
            return $this->redirectToRoute('binance-edit-rule', array('id' => $rule->getId()));
        }

        $btcPrice = $binanceService->getBtcNumberFormat(floatval($btcPrice));
        $buyLimit = $binanceService->getBtcNumberFormat(floatval($buyLimit));
        if (isset($stop) && $stop > 0) {
            $stop = $binanceService->getBtcNumberFormat(floatval($stop));
        }
        $rule->setBtcPrice($btcPrice);
        $rule->setStop($stop);
        $rule->setBuyLimit($buyLimit);
        $rule->setQuantity($quantity);
        $rule->setStopType($stopType);
        $binanceService->upsertRule($rule)->getId();

        $binanceService->setRulesToRedis($userService->get($this->getUser()->getId()));

        $this->flashBag->add('success', 'Rule is successfuly updated!');
        return $this->redirectToRoute('binance-rule-list', array('id' => $this->getUser()->getId()));

    }

    /**
     * @Route("/deleteRule/{id}", methods={"GET"}, name="binance-delete-rule")
     * @param null $id
     * @param BinanceService $binanceService
     * @return Response
     */
    public
    function deleteRuleAction($id = NULL, BinanceService $binanceService)
    {
        if (!$id) {
            return new Response(json_encode(array('status' => false, 'message' => 'Rule id not found!')), 404);
        }

        $rule = $binanceService->getRule(array('id' => $id, 'user' => $this->getUser()));

        $rules = $this->get('redis_service')->get('rules');
        if ($rules) {
            foreach ($rules[$rule->getSymbol()] as $key => $symbolRule) {
                if ($symbolRule['ruleId'] == $id) {
                    unset($rules[$rule->getSymbol()][$key]);
                    if (count($rules[$rule->getSymbol()]) < 1) {
                        unset($rules[$rule->getSymbol()]);
                    }
                    break;
                }
            }
            $this->get('redis_service')->insert('rules', $rules);
            $isDeleted = $binanceService->removeRule($rule);
            if ($isDeleted) {
                return new Response(json_encode(array('status' => true, 'message' => 'Successfully deleted.')), 200);
            }
        }
        return new Response(json_encode(array('status' => false, 'message' => 'Unsuccessfully deleted.')), 404);
    }

    /**
     * @Route("/rule/list/{id}", methods={"GET"}, name="binance-rule-list")
     * @param User $user
     * @param BinanceService $binanceService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public
    function listRuleAction(User $user, BinanceService $binanceService)
    {
        if (!$user || $user->getId() != $this->getUser()->getId()) {
            $this->flashBag->add('error', 'User is not found!');
            return $this->redirectToRoute('binance-coin-list');
        }

        $coins = $binanceService->getCoinsWithPrices();
        $rules = $binanceService->getRules(array('user' => $this->getUser()));

        return $this->render('@App/Binance/Rule/list-rule.html.twig',
            array('rules' => $rules, 'coins' => $coins)
        );

    }

}
