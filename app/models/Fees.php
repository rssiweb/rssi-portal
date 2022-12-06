<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Fees
 *
 * @ORM\Table(name="fees")
 * @ORM\Entity
 */
class Fees
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="fees_id_seq", allocationSize=1, initialValue=1)
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
     * @ORM\Column(name="sname", type="text", nullable=true)
     */
    private $sname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="studentid", type="text", nullable=true)
     */
    private $studentid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fees", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $fees;

    /**
     * @var int|null
     *
     * @ORM\Column(name="month", type="integer", nullable=true)
     */
    private $month;

    /**
     * @var string|null
     *
     * @ORM\Column(name="collectedby", type="text", nullable=true)
     */
    private $collectedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pstatus", type="text", nullable=true)
     */
    private $pstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ptype", type="text", nullable=true)
     */
    private $ptype;



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
     * @return Fees
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
     * Set sname.
     *
     * @param string|null $sname
     *
     * @return Fees
     */
    public function setSname($sname = null)
    {
        $this->sname = $sname;

        return $this;
    }

    /**
     * Get sname.
     *
     * @return string|null
     */
    public function getSname()
    {
        return $this->sname;
    }

    /**
     * Set studentid.
     *
     * @param string|null $studentid
     *
     * @return Fees
     */
    public function setStudentid($studentid = null)
    {
        $this->studentid = $studentid;

        return $this;
    }

    /**
     * Get studentid.
     *
     * @return string|null
     */
    public function getStudentid()
    {
        return $this->studentid;
    }

    /**
     * Set fees.
     *
     * @param string|null $fees
     *
     * @return Fees
     */
    public function setFees($fees = null)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees.
     *
     * @return string|null
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set month.
     *
     * @param int|null $month
     *
     * @return Fees
     */
    public function setMonth($month = null)
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get month.
     *
     * @return int|null
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set collectedby.
     *
     * @param string|null $collectedby
     *
     * @return Fees
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
     * Set pstatus.
     *
     * @param string|null $pstatus
     *
     * @return Fees
     */
    public function setPstatus($pstatus = null)
    {
        $this->pstatus = $pstatus;

        return $this;
    }

    /**
     * Get pstatus.
     *
     * @return string|null
     */
    public function getPstatus()
    {
        return $this->pstatus;
    }

    /**
     * Set ptype.
     *
     * @param string|null $ptype
     *
     * @return Fees
     */
    public function setPtype($ptype = null)
    {
        $this->ptype = $ptype;

        return $this;
    }

    /**
     * Get ptype.
     *
     * @return string|null
     */
    public function getPtype()
    {
        return $this->ptype;
    }
}
