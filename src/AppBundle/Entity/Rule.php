<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Rule
 *
 * @ORM\Table(name="rule")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RuleRepository")
 */
class Rule
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
     * @ORM\Column(name="buy_limit", type="string", length=255)
     */
    private $buyLimit;

    /**
     * @var string
     *
     * @ORM\Column(name="btc_price", type="string", length=255)
     */
    private $btcPrice;

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
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="rules")
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Bid", mappedBy="rule")
     */
    private $bid;


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
     * Set buyLimit
     *
     * @param string $buyLimit
     *
     * @return Rule
     */
    public function setBuyLimit($buyLimit)
    {
        $this->buyLimit = $buyLimit;

        return $this;
    }

    /**
     * Get buyLimit
     *
     * @return string
     */
    public function getBuyLimit()
    {
        return $this->buyLimit;
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
}
