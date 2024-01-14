<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * DistributionData
 *
 * @ORM\Table(name="distribution_data")
 * @ORM\Entity
 */
class DistributionData
{
    /**
     * @var int
     *
     * @ORM\Column(name="slno", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="distribution_data_slno_seq", allocationSize=1, initialValue=1)
     */
    private $slno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="distributedto", type="string", length=255, nullable=true)
     */
    private $distributedto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="distributedby", type="string", length=255, nullable=true)
     */
    private $distributedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="items", type="string", length=255, nullable=true)
     */
    private $items;

    /**
     * @var int|null
     *
     * @ORM\Column(name="quantity", type="integer", nullable=true)
     */
    private $quantity;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="issuance_date", type="date", nullable=true)
     */
    private $issuanceDate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;



    /**
     * Get slno.
     *
     * @return int
     */
    public function getSlno()
    {
        return $this->slno;
    }

    /**
     * Set distributedto.
     *
     * @param string|null $distributedto
     *
     * @return DistributionData
     */
    public function setDistributedto($distributedto = null)
    {
        $this->distributedto = $distributedto;

        return $this;
    }

    /**
     * Get distributedto.
     *
     * @return string|null
     */
    public function getDistributedto()
    {
        return $this->distributedto;
    }

    /**
     * Set distributedby.
     *
     * @param string|null $distributedby
     *
     * @return DistributionData
     */
    public function setDistributedby($distributedby = null)
    {
        $this->distributedby = $distributedby;

        return $this;
    }

    /**
     * Get distributedby.
     *
     * @return string|null
     */
    public function getDistributedby()
    {
        return $this->distributedby;
    }

    /**
     * Set items.
     *
     * @param string|null $items
     *
     * @return DistributionData
     */
    public function setItems($items = null)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Get items.
     *
     * @return string|null
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set quantity.
     *
     * @param int|null $quantity
     *
     * @return DistributionData
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set issuanceDate.
     *
     * @param \DateTime|null $issuanceDate
     *
     * @return DistributionData
     */
    public function setIssuanceDate($issuanceDate = null)
    {
        $this->issuanceDate = $issuanceDate;

        return $this;
    }

    /**
     * Get issuanceDate.
     *
     * @return \DateTime|null
     */
    public function getIssuanceDate()
    {
        return $this->issuanceDate;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return DistributionData
     */
    public function setTimestamp($timestamp = null)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp.
     *
     * @return \DateTime|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
