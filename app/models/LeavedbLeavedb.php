<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * LeavedbLeavedb
 *
 * @ORM\Table(name="leavedb_leavedb")
 * @ORM\Entity
 */
class LeavedbLeavedb
{
    /**
     * @var string
     *
     * @ORM\Column(name="leaveid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="leavedb_leavedb_leaveid_seq", allocationSize=1, initialValue=1)
     */
    private $leaveid;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="applicantid", type="text", nullable=true)
     */
    private $applicantid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="typeofleave", type="text", nullable=true)
     */
    private $typeofleave;

    /**
     * @var string|null
     *
     * @ORM\Column(name="doc", type="text", nullable=true)
     */
    private $doc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="text", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lyear", type="text", nullable=true)
     */
    private $lyear;

    /**
     * @var string|null
     *
     * @ORM\Column(name="creason", type="text", nullable=true)
     */
    private $creason;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="fromdate", type="date", nullable=true)
     */
    private $fromdate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="todate", type="date", nullable=true)
     */
    private $todate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_id", type="text", nullable=true)
     */
    private $reviewerId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_name", type="text", nullable=true)
     */
    private $reviewerName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appliedby", type="text", nullable=true)
     */
    private $appliedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="applicantcomment", type="text", nullable=true)
     */
    private $applicantcomment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="days", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $days;

    /**
     * @var string|null
     *
     * @ORM\Column(name="halfday", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $halfday;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ack", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $ack;



    /**
     * Get leaveid.
     *
     * @return string
     */
    public function getLeaveid()
    {
        return $this->leaveid;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return LeavedbLeavedb
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

    /**
     * Set applicantid.
     *
     * @param string|null $applicantid
     *
     * @return LeavedbLeavedb
     */
    public function setApplicantid($applicantid = null)
    {
        $this->applicantid = $applicantid;

        return $this;
    }

    /**
     * Get applicantid.
     *
     * @return string|null
     */
    public function getApplicantid()
    {
        return $this->applicantid;
    }

    /**
     * Set typeofleave.
     *
     * @param string|null $typeofleave
     *
     * @return LeavedbLeavedb
     */
    public function setTypeofleave($typeofleave = null)
    {
        $this->typeofleave = $typeofleave;

        return $this;
    }

    /**
     * Get typeofleave.
     *
     * @return string|null
     */
    public function getTypeofleave()
    {
        return $this->typeofleave;
    }

    /**
     * Set doc.
     *
     * @param string|null $doc
     *
     * @return LeavedbLeavedb
     */
    public function setDoc($doc = null)
    {
        $this->doc = $doc;

        return $this;
    }

    /**
     * Get doc.
     *
     * @return string|null
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return LeavedbLeavedb
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set comment.
     *
     * @param string|null $comment
     *
     * @return LeavedbLeavedb
     */
    public function setComment($comment = null)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set lyear.
     *
     * @param string|null $lyear
     *
     * @return LeavedbLeavedb
     */
    public function setLyear($lyear = null)
    {
        $this->lyear = $lyear;

        return $this;
    }

    /**
     * Get lyear.
     *
     * @return string|null
     */
    public function getLyear()
    {
        return $this->lyear;
    }

    /**
     * Set creason.
     *
     * @param string|null $creason
     *
     * @return LeavedbLeavedb
     */
    public function setCreason($creason = null)
    {
        $this->creason = $creason;

        return $this;
    }

    /**
     * Get creason.
     *
     * @return string|null
     */
    public function getCreason()
    {
        return $this->creason;
    }

    /**
     * Set fromdate.
     *
     * @param \DateTime|null $fromdate
     *
     * @return LeavedbLeavedb
     */
    public function setFromdate($fromdate = null)
    {
        $this->fromdate = $fromdate;

        return $this;
    }

    /**
     * Get fromdate.
     *
     * @return \DateTime|null
     */
    public function getFromdate()
    {
        return $this->fromdate;
    }

    /**
     * Set todate.
     *
     * @param \DateTime|null $todate
     *
     * @return LeavedbLeavedb
     */
    public function setTodate($todate = null)
    {
        $this->todate = $todate;

        return $this;
    }

    /**
     * Get todate.
     *
     * @return \DateTime|null
     */
    public function getTodate()
    {
        return $this->todate;
    }

    /**
     * Set reviewerId.
     *
     * @param string|null $reviewerId
     *
     * @return LeavedbLeavedb
     */
    public function setReviewerId($reviewerId = null)
    {
        $this->reviewerId = $reviewerId;

        return $this;
    }

    /**
     * Get reviewerId.
     *
     * @return string|null
     */
    public function getReviewerId()
    {
        return $this->reviewerId;
    }

    /**
     * Set reviewerName.
     *
     * @param string|null $reviewerName
     *
     * @return LeavedbLeavedb
     */
    public function setReviewerName($reviewerName = null)
    {
        $this->reviewerName = $reviewerName;

        return $this;
    }

    /**
     * Get reviewerName.
     *
     * @return string|null
     */
    public function getReviewerName()
    {
        return $this->reviewerName;
    }

    /**
     * Set appliedby.
     *
     * @param string|null $appliedby
     *
     * @return LeavedbLeavedb
     */
    public function setAppliedby($appliedby = null)
    {
        $this->appliedby = $appliedby;

        return $this;
    }

    /**
     * Get appliedby.
     *
     * @return string|null
     */
    public function getAppliedby()
    {
        return $this->appliedby;
    }

    /**
     * Set applicantcomment.
     *
     * @param string|null $applicantcomment
     *
     * @return LeavedbLeavedb
     */
    public function setApplicantcomment($applicantcomment = null)
    {
        $this->applicantcomment = $applicantcomment;

        return $this;
    }

    /**
     * Get applicantcomment.
     *
     * @return string|null
     */
    public function getApplicantcomment()
    {
        return $this->applicantcomment;
    }

    /**
     * Set days.
     *
     * @param string|null $days
     *
     * @return LeavedbLeavedb
     */
    public function setDays($days = null)
    {
        $this->days = $days;

        return $this;
    }

    /**
     * Get days.
     *
     * @return string|null
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Set halfday.
     *
     * @param string|null $halfday
     *
     * @return LeavedbLeavedb
     */
    public function setHalfday($halfday = null)
    {
        $this->halfday = $halfday;

        return $this;
    }

    /**
     * Get halfday.
     *
     * @return string|null
     */
    public function getHalfday()
    {
        return $this->halfday;
    }

    /**
     * Set ack.
     *
     * @param string|null $ack
     *
     * @return LeavedbLeavedb
     */
    public function setAck($ack = null)
    {
        $this->ack = $ack;

        return $this;
    }

    /**
     * Get ack.
     *
     * @return string|null
     */
    public function getAck()
    {
        return $this->ack;
    }
}
