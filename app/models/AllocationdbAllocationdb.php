<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * AllocationdbAllocationdb
 *
 * @ORM\Table(name="allocationdb_allocationdb")
 * @ORM\Entity
 */
class AllocationdbAllocationdb
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="allocationdb_allocationdb_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hallocationdate", type="string", length=512, nullable=true)
     */
    private $hallocationdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatenumber", type="string", length=512, nullable=true)
     */
    private $associatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hfullname", type="string", length=512, nullable=true)
     */
    private $hfullname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hstatus", type="string", length=512, nullable=true)
     */
    private $hstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hmaxclass", type="string", length=512, nullable=true)
     */
    private $hmaxclass;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hclasstaken", type="string", length=512, nullable=true)
     */
    private $hclasstaken;



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
     * Set hallocationdate.
     *
     * @param string|null $hallocationdate
     *
     * @return AllocationdbAllocationdb
     */
    public function setHallocationdate($hallocationdate = null)
    {
        $this->hallocationdate = $hallocationdate;

        return $this;
    }

    /**
     * Get hallocationdate.
     *
     * @return string|null
     */
    public function getHallocationdate()
    {
        return $this->hallocationdate;
    }

    /**
     * Set associatenumber.
     *
     * @param string|null $associatenumber
     *
     * @return AllocationdbAllocationdb
     */
    public function setAssociatenumber($associatenumber = null)
    {
        $this->associatenumber = $associatenumber;

        return $this;
    }

    /**
     * Get associatenumber.
     *
     * @return string|null
     */
    public function getAssociatenumber()
    {
        return $this->associatenumber;
    }

    /**
     * Set hfullname.
     *
     * @param string|null $hfullname
     *
     * @return AllocationdbAllocationdb
     */
    public function setHfullname($hfullname = null)
    {
        $this->hfullname = $hfullname;

        return $this;
    }

    /**
     * Get hfullname.
     *
     * @return string|null
     */
    public function getHfullname()
    {
        return $this->hfullname;
    }

    /**
     * Set hstatus.
     *
     * @param string|null $hstatus
     *
     * @return AllocationdbAllocationdb
     */
    public function setHstatus($hstatus = null)
    {
        $this->hstatus = $hstatus;

        return $this;
    }

    /**
     * Get hstatus.
     *
     * @return string|null
     */
    public function getHstatus()
    {
        return $this->hstatus;
    }

    /**
     * Set hmaxclass.
     *
     * @param string|null $hmaxclass
     *
     * @return AllocationdbAllocationdb
     */
    public function setHmaxclass($hmaxclass = null)
    {
        $this->hmaxclass = $hmaxclass;

        return $this;
    }

    /**
     * Get hmaxclass.
     *
     * @return string|null
     */
    public function getHmaxclass()
    {
        return $this->hmaxclass;
    }

    /**
     * Set hclasstaken.
     *
     * @param string|null $hclasstaken
     *
     * @return AllocationdbAllocationdb
     */
    public function setHclasstaken($hclasstaken = null)
    {
        $this->hclasstaken = $hclasstaken;

        return $this;
    }

    /**
     * Get hclasstaken.
     *
     * @return string|null
     */
    public function getHclasstaken()
    {
        return $this->hclasstaken;
    }
}
