<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * GpsHistory
 *
 * @ORM\Table(name="gps_history")
 * @ORM\Entity
 */
class GpsHistory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="gps_history_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="itemname", type="text", nullable=true)
     */
    private $itemname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quantity", type="text", nullable=true)
     */
    private $quantity;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectedby", type="text", nullable=true)
     */
    private $collectedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="itemtype", type="text", nullable=true)
     */
    private $itemtype;

    /**
     * @var string
     *
     * @ORM\Column(name="itemid", type="text", nullable=false)
     */
    private $itemid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="taggedto", type="text", nullable=true)
     */
    private $taggedto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="asset_status", type="text", nullable=true)
     */
    private $assetStatus;



    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return GpsHistory
     */
    public function setDate($date = null)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set itemname.
     *
     * @param string|null $itemname
     *
     * @return GpsHistory
     */
    public function setItemname($itemname = null)
    {
        $this->itemname = $itemname;

        return $this;
    }

    /**
     * Get itemname.
     *
     * @return string|null
     */
    public function getItemname()
    {
        return $this->itemname;
    }

    /**
     * Set quantity.
     *
     * @param string|null $quantity
     *
     * @return GpsHistory
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return string|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return GpsHistory
     */
    public function setRemarks($remarks = null)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string|null
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set collectedby.
     *
     * @param string|null $collectedby
     *
     * @return GpsHistory
     */
    public function setCollectedby($collectedby = null)
    {
        $this->collectedby = $collectedby;

        return $this;
    }

    /**
     * Get collectedby.
     *
     * @return string|null
     */
    public function getCollectedby()
    {
        return $this->collectedby;
    }

    /**
     * Set itemtype.
     *
     * @param string|null $itemtype
     *
     * @return GpsHistory
     */
    public function setItemtype($itemtype = null)
    {
        $this->itemtype = $itemtype;

        return $this;
    }

    /**
     * Get itemtype.
     *
     * @return string|null
     */
    public function getItemtype()
    {
        return $this->itemtype;
    }

    /**
     * Set itemid.
     *
     * @param string $itemid
     *
     * @return GpsHistory
     */
    public function setItemid($itemid)
    {
        $this->itemid = $itemid;

        return $this;
    }

    /**
     * Get itemid.
     *
     * @return string
     */
    public function getItemid()
    {
        return $this->itemid;
    }

    /**
     * Set taggedto.
     *
     * @param string|null $taggedto
     *
     * @return GpsHistory
     */
    public function setTaggedto($taggedto = null)
    {
        $this->taggedto = $taggedto;

        return $this;
    }

    /**
     * Get taggedto.
     *
     * @return string|null
     */
    public function getTaggedto()
    {
        return $this->taggedto;
    }

    /**
     * Set assetStatus.
     *
     * @param string|null $assetStatus
     *
     * @return GpsHistory
     */
    public function setAssetStatus($assetStatus = null)
    {
        $this->assetStatus = $assetStatus;

        return $this;
    }

    /**
     * Get assetStatus.
     *
     * @return string|null
     */
    public function getAssetStatus()
    {
        return $this->assetStatus;
    }
}
