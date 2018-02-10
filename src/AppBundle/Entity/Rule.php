<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use JsonSerializable;

/**
 * Rule
 *
 * @ORM\Table(name="rule")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RuleRepository")
 */
class Rule implements JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="symbol", type="string", length=255)
     */
    private $symbol;

    /**
     * @var string
     *
     * @ORM\Column(name="stop", type="string", length=255, nullable=true)
     */
    private $stop;

    /**
     * @var string
     *
     * @ORM\Column(name="stop_type", type="string", length=255, nullable=true)
     */
    private $stopType;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_limit", type="string", length=255)
     */
    private $ruleLimit;

    /**
     * @var string
     *
     * @ORM\Column(name="btc_price", type="string", length=255)
     */
    private $btcPrice;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="quantity", type="integer", length=255)
     */
    private $quantity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     *
     * @ORM\Column(name="is_done", type="boolean")
     */
    private $isDone;

    /**
     *
     * @ORM\Column(name="gain_btc_price", type="string", nullable=true)
     */
    private $gainBtcPrice;

    /**
     *
     * @ORM\Column(name="gain_quantity", type="string", nullable=true)
     */
    private $gainQuantity;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="rules")
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Bid", mappedBy="rule")
     */
    private $bid;

    /**
     * One Student has One Student.
     * @OneToOne(targetEntity="AppBundle\Entity\Rule")
     * @JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $parentRule;

    private $haveParentOrChildRule = false;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set symbol
     *
     * @param string $symbol
     *
     * @return Rule
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Rule
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Rule
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Set isDone
     *
     * @param boolean $isDone
     *
     * @return Rule
     */
    public function setIsDone($isDone)
    {
        $this->isDone = $isDone;

        return $this;
    }

    /**
     * Get isDone
     *
     * @return boolean
     */
    public function getIsDone()
    {
        return $this->isDone;
    }

    /**
     * Set stop
     *
     * @param string $stop
     *
     * @return Rule
     */
    public function setStop($stop)
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Get stop
     *
     * @return string
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     *
     * @param $ruleLimit
     * @return Rule
     */
    public function setRuleLimit($ruleLimit)
    {
        $this->ruleLimit = $ruleLimit;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getRuleLimit()
    {
        return $this->ruleLimit;
    }

    /**
     * Set btcPrice
     *
     * @param string $btcPrice
     *
     * @return Rule
     */
    public function setBtcPrice($btcPrice)
    {
        $this->btcPrice = $btcPrice;

        return $this;
    }

    /**
     * Get btcPrice
     *
     * @return string
     */
    public function getBtcPrice()
    {
        return $this->btcPrice;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return Rule
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set bid
     *
     * @param \AppBundle\Entity\Bid $bid
     *
     * @return Rule
     */
    public function setBid(\AppBundle\Entity\Bid $bid = null)
    {
        $this->bid = $bid;

        return $this;
    }

    /**
     * Get bid
     *
     * @return \AppBundle\Entity\Bid
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * Set stopType
     *
     * @param string $stopType
     *
     * @return Rule
     */
    public function setStopType($stopType)
    {
        $this->stopType = $stopType;

        return $this;
    }

    /**
     * Get stopType
     *
     * @return string
     */
    public function getStopType()
    {
        return $this->stopType;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Rule
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'symbol' => $this->getSymbol(),
            'createdAt' => $this->getCreatedAt()->format('d.m.Y H:i:s'),
            'isDone' => $this->getIsDone(),
            'stop' => $this->getStop(),
            'ruleLimit' => $this->getRuleLimit(),
            'btcPrice' => $this->getBtcPrice(),
            'quantity' => $this->getQuantity(),
            'stopType' => $this->getStopType(),
            'type' => $this->getType(),
            'gainBtcPrice' => $this->getGainBtcPrice(),
            'parentRule' => $this->getParentRule(),
            'gainQuantity' => $this->getGainQuantity(),
            'haveParentOrChildRule' => $this->getHaveParentOrChildRule()
        );
    }

    /**
     * Set gainBtcPrice
     *
     * @param string $gainBtcPrice
     *
     * @return Rule
     */
    public function setGainBtcPrice($gainBtcPrice)
    {
        $this->gainBtcPrice = $gainBtcPrice;

        return $this;
    }

    /**
     * Get gainBtcPrice
     *
     * @return string
     */
    public function getGainBtcPrice()
    {
        return $this->gainBtcPrice;
    }

    /**
     * Set parentRule
     *
     * @param \AppBundle\Entity\Rule $parentRule
     *
     * @return Rule
     */
    public function setParentRule(\AppBundle\Entity\Rule $parentRule = null)
    {
        $this->parentRule = $parentRule;

        return $this;
    }

    /**
     * Get parentRule
     *
     * @return \AppBundle\Entity\Rule
     */
    public function getParentRule()
    {
        return $this->parentRule;
    }

    /**
     * Set gainQuantity
     *
     * @param string $gainQuantity
     *
     * @return Rule
     */
    public function setGainQuantity($gainQuantity)
    {
        $this->gainQuantity = $gainQuantity;

        return $this;
    }

    /**
     * Get gainQuantity
     *
     * @return string
     */
    public function getGainQuantity()
    {
        return $this->gainQuantity;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getHaveParentOrChildRule()
    {
        return $this->haveParentOrChildRule;
    }

    /**
     * Set symbol
     *
     * @param bool $haveParentOrChildRule
     * @return Rule
     */
    public function setHaveParentOrChildRule($haveParentOrChildRule = false)
    {
        $this->haveParentOrChildRule = $haveParentOrChildRule;

        return $this;
    }
}
