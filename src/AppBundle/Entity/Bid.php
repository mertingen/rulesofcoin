<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Bid
 *
 * @ORM\Table(name="bid")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BidRepository")
 */
class Bid implements \JsonSerializable
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
     * @var int
     *
     * @ORM\Column(name="order_id", type="string", length=255)
     */
    private $orderId;

    /**
     * @var int
     *
     * @ORM\Column(name="client_order_id", type="string", length=255)
     */
    private $clientOrderId;

    /**
     * @var int
     *
     * @ORM\Column(name="executed_quantity", type="string", length=255)
     */
    private $executedQuantity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Rule", inversedBy="bid")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $rule;


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
     * Set clientOrderId
     *
     * @param integer $clientOrderId
     *
     * @return Bid
     */
    public function setClientOrderId($clientOrderId)
    {
        $this->clientOrderId = $clientOrderId;

        return $this;
    }

    /**
     * Get clientOrderId
     *
     * @return int
     */
    public function getClientOrderId()
    {
        return $this->clientOrderId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Bid
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
     * Set status
     *
     * @param string $status
     *
     * @return Bid
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set rule
     *
     * @param \AppBundle\Entity\Rule $rule
     *
     * @return Bid
     */
    public function setRule(\AppBundle\Entity\Rule $rule = null)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Get rule
     *
     * @return \AppBundle\Entity\Rule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set orderId
     *
     * @param string $orderId
     *
     * @return Bid
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set executedQuantity
     *
     * @param string $executedQuantity
     *
     * @return Bid
     */
    public function setExecutedQuantity($executedQuantity)
    {
        $this->executedQuantity = $executedQuantity;

        return $this;
    }

    /**
     * Get executedQuantity
     *
     * @return string
     */
    public function getExecutedQuantity()
    {
        return $this->executedQuantity;
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
            'createdAt' => $this->getCreatedAt()->format('d.m.Y H:i:s'),
            'status' => $this->getStatus(),
            'orderId' => $this->getOrderId(),
            'clientOrderId' => $this->getClientOrderId(),
            'executedQuantity' => $this->getExecutedQuantity()
        );
    }
}
