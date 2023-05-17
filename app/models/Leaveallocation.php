<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Leaveallocation
 *
 * @ORM\Table(name="leaveallocation")
 * @ORM\Entity
 */
class Leaveallocation
{
    /**
     * @var string
     *
     * @ORM\Column(name="leaveallocationid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="leaveallocation_leaveallocationid_seq", allocationSize=1, initialValue=1)
     */
    private $leaveallocationid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allo_applicantid", type="text", nullable=true)
     */
    private $alloApplicantid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allo_daycount", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $alloDaycount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allo_leavetype", type="text", nullable=true)
     */
    private $alloLeavetype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allo_remarks", type="text", nullable=true)
     */
    private $alloRemarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allocatedbyid", type="text", nullable=true)
     */
    private $allocatedbyid;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="allo_date", type="datetime", nullable=true)
     */
    private $alloDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allocatedbyname", type="text", nullable=true)
     */
    private $allocatedbyname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allo_academicyear", type="text", nullable=true)
     */
    private $alloAcademicyear;



    /**
     * Get leaveallocationid.
     *
     * @return string
     */
    public function getLeaveallocationid()
    {
        return $this->leaveallocationid;
    }

    /**
     * Set alloApplicantid.
     *
     * @param string|null $alloApplicantid
     *
     * @return Leaveallocation
     */
    public function setAlloApplicantid($alloApplicantid = null)
    {
        $this->alloApplicantid = $alloApplicantid;

        return $this;
    }

    /**
     * Get alloApplicantid.
     *
     * @return string|null
     */
    public function getAlloApplicantid()
    {
        return $this->alloApplicantid;
    }

    /**
     * Set alloDaycount.
     *
     * @param string|null $alloDaycount
     *
     * @return Leaveallocation
     */
    public function setAlloDaycount($alloDaycount = null)
    {
        $this->alloDaycount = $alloDaycount;

        return $this;
    }

    /**
     * Get alloDaycount.
     *
     * @return string|null
     */
    public function getAlloDaycount()
    {
        return $this->alloDaycount;
    }

    /**
     * Set alloLeavetype.
     *
     * @param string|null $alloLeavetype
     *
     * @return Leaveallocation
     */
    public function setAlloLeavetype($alloLeavetype = null)
    {
        $this->alloLeavetype = $alloLeavetype;

        return $this;
    }

    /**
     * Get alloLeavetype.
     *
     * @return string|null
     */
    public function getAlloLeavetype()
    {
        return $this->alloLeavetype;
    }

    /**
     * Set alloRemarks.
     *
     * @param string|null $alloRemarks
     *
     * @return Leaveallocation
     */
    public function setAlloRemarks($alloRemarks = null)
    {
        $this->alloRemarks = $alloRemarks;

        return $this;
    }

    /**
     * Get alloRemarks.
     *
     * @return string|null
     */
    public function getAlloRemarks()
    {
        return $this->alloRemarks;
    }

    /**
     * Set allocatedbyid.
     *
     * @param string|null $allocatedbyid
     *
     * @return Leaveallocation
     */
    public function setAllocatedbyid($allocatedbyid = null)
    {
        $this->allocatedbyid = $allocatedbyid;

        return $this;
    }

    /**
     * Get allocatedbyid.
     *
     * @return string|null
     */
    public function getAllocatedbyid()
    {
        return $this->allocatedbyid;
    }

    /**
     * Set alloDate.
     *
     * @param \DateTime|null $alloDate
     *
     * @return Leaveallocation
     */
    public function setAlloDate($alloDate = null)
    {
        $this->alloDate = $alloDate;

        return $this;
    }

    /**
     * Get alloDate.
     *
     * @return \DateTime|null
     */
    public function getAlloDate()
    {
        return $this->alloDate;
    }

    /**
     * Set allocatedbyname.
     *
     * @param string|null $allocatedbyname
     *
     * @return Leaveallocation
     */
    public function setAllocatedbyname($allocatedbyname = null)
    {
        $this->allocatedbyname = $allocatedbyname;

        return $this;
    }

    /**
     * Get allocatedbyname.
     *
     * @return string|null
     */
    public function getAllocatedbyname()
    {
        return $this->allocatedbyname;
    }

    /**
     * Set alloAcademicyear.
     *
     * @param string|null $alloAcademicyear
     *
     * @return Leaveallocation
     */
    public function setAlloAcademicyear($alloAcademicyear = null)
    {
        $this->alloAcademicyear = $alloAcademicyear;

        return $this;
    }

    /**
     * Get alloAcademicyear.
     *
     * @return string|null
     */
    public function getAlloAcademicyear()
    {
        return $this->alloAcademicyear;
    }
}
