<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Leaveadjustment
 *
 * @ORM\Table(name="leaveadjustment")
 * @ORM\Entity
 */
class Leaveadjustment
{
    /**
     * @var string
     *
     * @ORM\Column(name="leaveadjustmentid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="leaveadjustment_leaveadjustmentid_seq", allocationSize=1, initialValue=1)
     */
    private $leaveadjustmentid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_applicantid", type="text", nullable=true)
     */
    private $adjApplicantid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_day", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $adjDay;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_academicyear", type="text", nullable=true)
     */
    private $adjAcademicyear;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="adj_fromdate", type="date", nullable=true)
     */
    private $adjFromdate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="adj_todate", type="date", nullable=true)
     */
    private $adjTodate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="adj_regdate", type="datetime", nullable=true)
     */
    private $adjRegdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_reason", type="text", nullable=true)
     */
    private $adjReason;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_leavetype", type="text", nullable=true)
     */
    private $adjLeavetype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_appliedby", type="text", nullable=true)
     */
    private $adjAppliedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adj_appliedby_name", type="text", nullable=true)
     */
    private $adjAppliedbyName;



    /**
     * Get leaveadjustmentid.
     *
     * @return string
     */
    public function getLeaveadjustmentid()
    {
        return $this->leaveadjustmentid;
    }

    /**
     * Set adjApplicantid.
     *
     * @param string|null $adjApplicantid
     *
     * @return Leaveadjustment
     */
    public function setAdjApplicantid($adjApplicantid = null)
    {
        $this->adjApplicantid = $adjApplicantid;

        return $this;
    }

    /**
     * Get adjApplicantid.
     *
     * @return string|null
     */
    public function getAdjApplicantid()
    {
        return $this->adjApplicantid;
    }

    /**
     * Set adjDay.
     *
     * @param string|null $adjDay
     *
     * @return Leaveadjustment
     */
    public function setAdjDay($adjDay = null)
    {
        $this->adjDay = $adjDay;

        return $this;
    }

    /**
     * Get adjDay.
     *
     * @return string|null
     */
    public function getAdjDay()
    {
        return $this->adjDay;
    }

    /**
     * Set adjAcademicyear.
     *
     * @param string|null $adjAcademicyear
     *
     * @return Leaveadjustment
     */
    public function setAdjAcademicyear($adjAcademicyear = null)
    {
        $this->adjAcademicyear = $adjAcademicyear;

        return $this;
    }

    /**
     * Get adjAcademicyear.
     *
     * @return string|null
     */
    public function getAdjAcademicyear()
    {
        return $this->adjAcademicyear;
    }

    /**
     * Set adjFromdate.
     *
     * @param \DateTime|null $adjFromdate
     *
     * @return Leaveadjustment
     */
    public function setAdjFromdate($adjFromdate = null)
    {
        $this->adjFromdate = $adjFromdate;

        return $this;
    }

    /**
     * Get adjFromdate.
     *
     * @return \DateTime|null
     */
    public function getAdjFromdate()
    {
        return $this->adjFromdate;
    }

    /**
     * Set adjTodate.
     *
     * @param \DateTime|null $adjTodate
     *
     * @return Leaveadjustment
     */
    public function setAdjTodate($adjTodate = null)
    {
        $this->adjTodate = $adjTodate;

        return $this;
    }

    /**
     * Get adjTodate.
     *
     * @return \DateTime|null
     */
    public function getAdjTodate()
    {
        return $this->adjTodate;
    }

    /**
     * Set adjRegdate.
     *
     * @param \DateTime|null $adjRegdate
     *
     * @return Leaveadjustment
     */
    public function setAdjRegdate($adjRegdate = null)
    {
        $this->adjRegdate = $adjRegdate;

        return $this;
    }

    /**
     * Get adjRegdate.
     *
     * @return \DateTime|null
     */
    public function getAdjRegdate()
    {
        return $this->adjRegdate;
    }

    /**
     * Set adjReason.
     *
     * @param string|null $adjReason
     *
     * @return Leaveadjustment
     */
    public function setAdjReason($adjReason = null)
    {
        $this->adjReason = $adjReason;

        return $this;
    }

    /**
     * Get adjReason.
     *
     * @return string|null
     */
    public function getAdjReason()
    {
        return $this->adjReason;
    }

    /**
     * Set adjLeavetype.
     *
     * @param string|null $adjLeavetype
     *
     * @return Leaveadjustment
     */
    public function setAdjLeavetype($adjLeavetype = null)
    {
        $this->adjLeavetype = $adjLeavetype;

        return $this;
    }

    /**
     * Get adjLeavetype.
     *
     * @return string|null
     */
    public function getAdjLeavetype()
    {
        return $this->adjLeavetype;
    }

    /**
     * Set adjAppliedby.
     *
     * @param string|null $adjAppliedby
     *
     * @return Leaveadjustment
     */
    public function setAdjAppliedby($adjAppliedby = null)
    {
        $this->adjAppliedby = $adjAppliedby;

        return $this;
    }

    /**
     * Get adjAppliedby.
     *
     * @return string|null
     */
    public function getAdjAppliedby()
    {
        return $this->adjAppliedby;
    }

    /**
     * Set adjAppliedbyName.
     *
     * @param string|null $adjAppliedbyName
     *
     * @return Leaveadjustment
     */
    public function setAdjAppliedbyName($adjAppliedbyName = null)
    {
        $this->adjAppliedbyName = $adjAppliedbyName;

        return $this;
    }

    /**
     * Get adjAppliedbyName.
     *
     * @return string|null
     */
    public function getAdjAppliedbyName()
    {
        return $this->adjAppliedbyName;
    }
}
