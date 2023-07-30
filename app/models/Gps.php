<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Gps
 *
 * @ORM\Table(name="gps")
 * @ORM\Entity
 */
class Gps
{
    /**
     * @var string
     *
     * @ORM\Column(name="itemid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="gps_itemid_seq", allocationSize=1, initialValue=1)
     */
    private $itemid;

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
     * @var string|null
     *
     * @ORM\Column(name="taggedto", type="text", nullable=true)
     */
    private $taggedto;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="lastupdatedon", type="datetime", nullable=true)
     */
    private $lastupdatedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="asset_status", type="text", nullable=true)
     */
    private $assetStatus;



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
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return Gps
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
     * @return Gps
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
     * @return Gps
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
     * @return Gps
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
     * @return Gps
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
     * @return Gps
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
     * Set taggedto.
     *
     * @param string|null $taggedto
     *
     * @return Gps
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
     * Set lastupdatedon.
     *
     * @param \DateTime|null $lastupdatedon
     *
     * @return Gps
     */
    public function setLastupdatedon($lastupdatedon = null)
    {
        $this->lastupdatedon = $lastupdatedon;

        return $this;
    }

    /**
     * Get lastupdatedon.
     *
     * @return \DateTime|null
     */
    public function getLastupdatedon()
    {
        return $this->lastupdatedon;
    }

    /**
     * Set assetStatus.
     *
     * @param string|null $assetStatus
     *
     * @return Gps
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
