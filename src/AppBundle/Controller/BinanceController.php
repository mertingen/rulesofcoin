<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Rule;
use AppBundle\Entity\User;
use AppBundle\Service\BinanceService;
use AppBundle\Service\RedisService;
use AppBundle\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class KeyController
 * @package AppBundle\Controller
 * @Route("/binance")
 */
class BinanceController extends Controller
{
    /**
     * @Route("/coins", methods={"GET"}, name="binance-coin-list")
     * @param BinanceService $binanceService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function coinsAction(BinanceService $binanceService)
    {
        $coins = $binanceService->getCoins();
        if (!$coins) {
            dump('coins not valid!');
            die;
        }
        return $this->render('@App/Binance/Coin/list-coin.html.twig',
            array('coins' => $coins)
        );
    }

    /**
     * @Route("/rule/{symbol}", methods={"GET"}, name="binance-add-rule")
     * @param null $symbol
     * @param BinanceService $binanceService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addRuleAction($symbol = NULL, BinanceService $binanceService)
    {
        $symbols = $this->get('redis_service')->get('symbols');
        if (!in_array($symbol, $symbols)) {
            dump('symbol is not valid!');
            die;
        }

        $where = array(
            'user' => $this->getUser(),
            'symbol' => $symbol
        );

        $isRule = $binanceService->getRule($where);
        if ($isRule) {
            dump('Exist rule!');
            die;
        }

        $data = $binanceService->getCoins($symbol);

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
    public function postAddRuleAction($symbol = NULL, BinanceService $binanceService, Request $request, UserService $userService)
    {
        $symbols = $this->get('redis_service')->get('symbols');

        if (!in_array($symbol, $symbols)) {
            dump('symbol is not valid!');
            die;
        }

        $where = array(
            'user' => $this->getUser(),
            'symbol' => $symbol
        );

        $isRule = $binanceService->getRule($where);
        if ($isRule) {
            dump('Exist rule!');
            die;
        }

        $price = $request->request->get('price', null);

        if (empty($price)) {
            dump('Price not found!');
            die;
        }

        if (strlen($price) !== 10) {
            dump('Price not valid!');
            die;
        }

        /*$isAddedRule = $binanceService->getRule(array('symbol' => $symbol, 'user' => $this->getUser()));
        if ($isAddedRule) {
            dump('Same rule found!');
            die;
        }*/

        $user = $userService->get($this->getUser()->getId());

        $rule = new Rule();
        $rule->setUser($user);
        $rule->setSymbol($symbol);
        $rule->setCreatedAt(new \DateTime());
        $rule->setPrice($price);
        $rule->setIsDone(false);

        $ruleId = $binanceService->upsertRule($rule)->getId();

        $binanceService->setRulesToRedis($user);

        return $this->redirectToRoute('binance-rule-list', array('id' => $user->getId()));

    }

    /**
     * @Route("/editRule/{id}", methods={"GET"}, name="binance-edit-rule")
     * @param null $id
     * @param BinanceService $binanceService
     * @return Response
     */
    public function editRuleAction($id = NULL, BinanceService $binanceService)
    {
        if (!$id) {
            dump('Rule Id not found!');
            die;
        }

        $rule = $binanceService->getRule(array('id' => $id, 'user' => $this->getUser()));

        $symbols = $this->get('redis_service')->get('symbols');
        if (!in_array($rule->getSymbol(), $symbols)) {
            dump('symbol is not valid!');
            die;
        }

        if (!$rule) {
            dump('Rule not found!');
            die;
        }

        $data = $binanceService->getCoins($rule->getSymbol());

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
    public function postEditRuleAction($id = NULL, BinanceService $binanceService, Request $request, UserService $userService)
    {
        if (!$id) {
            dump('Rule Id not found!');
            die;
        }

        $rule = $binanceService->getRule(array('id' => $id, 'user' => $this->getUser()));
        if (!$rule) {
            dump('Rule not found!');
            die;
        }

        $symbols = $this->get('redis_service')->get('symbols');
        if (!in_array($rule->getSymbol(), $symbols)) {
            dump('symbol is not valid!');
            die;
        }

        $price = $request->request->get('price', null);
        if (!$price) {
            dump('Price not found!');
            die;
        }
        if (strlen($price) !== 10) {
            dump('Price not valid!');
            die;
        }

        $rule->setPrice($price);
        $binanceService->upsertRule($rule)->getId();

        $binanceService->setRulesToRedis($userService->get(array('user' => $this->getUser())));

        return $this->redirectToRoute('binance-rule-list', array('id' => $this->getUser()->getId()));

    }

    /**
     * @Route("/deleteRule/{id}", methods={"GET"}, name="binance-delete-rule")
     * @param null $id
     * @param BinanceService $binanceService
     * @return Response
     */
    public function deleteRuleAction($id = NULL, BinanceService $binanceService)
    {
        if (!$id) {
            dump('Rule Id not found!');
            die;
        }

        $rule = $binanceService->getRule(array('id' => $id, 'user' => $this->getUser()));

        $rules = $this->get('redis_service')->get('rules');
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
        if (!$isDeleted) {
            return new Response(json_encode(array('status' => false, 'message' => 'Unsuccessfully deleted.')), 404);
        }
        return new Response(json_encode(array('status' => true, 'message' => 'Successfully deleted.')), 200);
    }

    /**
     * @Route("/rule/list/{id}", methods={"GET"}, name="binance-rule-list")
     * @param User $user
     * @param BinanceService $binanceService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listRuleAction(User $user, BinanceService $binanceService)
    {
        if (!$user || $user->getId() != $this->getUser()->getId()) {
            dump('User not found!');
            die;
        }

        $rules = $binanceService->getRules(array('user' => $this->getUser()));

        return $this->render('@App/Binance/Rule/list-rule.html.twig',
            array('rules' => $rules)
        );

    }

}
