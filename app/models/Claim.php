<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Claim
 *
 * @ORM\Table(name="claim")
 * @ORM\Entity
 */
class Claim
{
    /**
     * @var string
     *
     * @ORM\Column(name="reimbid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="claim_reimbid_seq", allocationSize=1, initialValue=1)
     */
    private $reimbid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="registrationid", type="text", nullable=true)
     */
    private $registrationid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="selectclaimheadfromthelistbelow", type="text", nullable=true)
     */
    private $selectclaimheadfromthelistbelow;

    /**
     * @var string|null
     *
     * @ORM\Column(name="billno", type="text", nullable=true)
     */
    private $billno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="currency", type="text", nullable=true)
     */
    private $currency;

    /**
     * @var string|null
     *
     * @ORM\Column(name="totalbillamount", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $totalbillamount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uploadeddocuments", type="text", nullable=true)
     */
    private $uploadeddocuments;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ack", type="text", nullable=true)
     */
    private $ack;

    /**
     * @var string|null
     *
     * @ORM\Column(name="year", type="text", nullable=true)
     */
    private $year;

    /**
     * @var string|null
     *
     * @ORM\Column(name="claimstatus", type="text", nullable=true)
     */
    private $claimstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="approvedamount", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $approvedamount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="transactionid", type="text", nullable=true)
     */
    private $transactionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mediremarks", type="text", nullable=true)
     */
    private $mediremarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="claimheaddetails", type="text", nullable=true)
     */
    private $claimheaddetails;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="transfereddate", type="date", nullable=true)
     */
    private $transfereddate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="closedon", type="date", nullable=true)
     */
    private $closedon;

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
     * @var \DateTime|null
     *
     * @ORM\Column(name="updatedon", type="datetime", nullable=true)
     */
    private $updatedon;



    /**
     * Get reimbid.
     *
     * @return string
     */
    public function getReimbid()
    {
        return $this->reimbid;
    }

    /**
     * Set registrationid.
     *
     * @param string|null $registrationid
     *
     * @return Claim
     */
    public function setRegistrationid($registrationid = null)
    {
        $this->registrationid = $registrationid;

        return $this;
    }

    /**
     * Get registrationid.
     *
     * @return string|null
     */
    public function getRegistrationid()
    {
        return $this->registrationid;
    }

    /**
     * Set selectclaimheadfromthelistbelow.
     *
     * @param string|null $selectclaimheadfromthelistbelow
     *
     * @return Claim
     */
    public function setSelectclaimheadfromthelistbelow($selectclaimheadfromthelistbelow = null)
    {
        $this->selectclaimheadfromthelistbelow = $selectclaimheadfromthelistbelow;

        return $this;
    }

    /**
     * Get selectclaimheadfromthelistbelow.
     *
     * @return string|null
     */
    public function getSelectclaimheadfromthelistbelow()
    {
        return $this->selectclaimheadfromthelistbelow;
    }

    /**
     * Set billno.
     *
     * @param string|null $billno
     *
     * @return Claim
     */
    public function setBillno($billno = null)
    {
        $this->billno = $billno;

        return $this;
    }

    /**
     * Get billno.
     *
     * @return string|null
     */
    public function getBillno()
    {
        return $this->billno;
    }

    /**
     * Set currency.
     *
     * @param string|null $currency
     *
     * @return Claim
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set totalbillamount.
     *
     * @param string|null $totalbillamount
     *
     * @return Claim
     */
    public function setTotalbillamount($totalbillamount = null)
    {
        $this->totalbillamount = $totalbillamount;

        return $this;
    }

    /**
     * Get totalbillamount.
     *
     * @return string|null
     */
    public function getTotalbillamount()
    {
        return $this->totalbillamount;
    }

    /**
     * Set uploadeddocuments.
     *
     * @param string|null $uploadeddocuments
     *
     * @return Claim
     */
    public function setUploadeddocuments($uploadeddocuments = null)
    {
        $this->uploadeddocuments = $uploadeddocuments;

        return $this;
    }

    /**
     * Get uploadeddocuments.
     *
     * @return string|null
     */
    public function getUploadeddocuments()
    {
        return $this->uploadeddocuments;
    }

    /**
     * Set ack.
     *
     * @param string|null $ack
     *
     * @return Claim
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

    /**
     * Set year.
     *
     * @param string|null $year
     *
     * @return Claim
     */
    public function setYear($year = null)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year.
     *
     * @return string|null
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set claimstatus.
     *
     * @param string|null $claimstatus
     *
     * @return Claim
     */
    public function setClaimstatus($claimstatus = null)
    {
        $this->claimstatus = $claimstatus;

        return $this;
    }

    /**
     * Get claimstatus.
     *
     * @return string|null
     */
    public function getClaimstatus()
    {
        return $this->claimstatus;
    }

    /**
     * Set approvedamount.
     *
     * @param string|null $approvedamount
     *
     * @return Claim
     */
    public function setApprovedamount($approvedamount = null)
    {
        $this->approvedamount = $approvedamount;

        return $this;
    }

    /**
     * Get approvedamount.
     *
     * @return string|null
     */
    public function getApprovedamount()
    {
        return $this->approvedamount;
    }

    /**
     * Set transactionid.
     *
     * @param string|null $transactionid
     *
     * @return Claim
     */
    public function setTransactionid($transactionid = null)
    {
        $this->transactionid = $transactionid;

        return $this;
    }

    /**
     * Get transactionid.
     *
     * @return string|null
     */
    public function getTransactionid()
    {
        return $this->transactionid;
    }

    /**
     * Set mediremarks.
     *
     * @param string|null $mediremarks
     *
     * @return Claim
     */
    public function setMediremarks($mediremarks = null)
    {
        $this->mediremarks = $mediremarks;

        return $this;
    }

    /**
     * Get mediremarks.
     *
     * @return string|null
     */
    public function getMediremarks()
    {
        return $this->mediremarks;
    }

    /**
     * Set claimheaddetails.
     *
     * @param string|null $claimheaddetails
     *
     * @return Claim
     */
    public function setClaimheaddetails($claimheaddetails = null)
    {
        $this->claimheaddetails = $claimheaddetails;

        return $this;
    }

    /**
     * Get claimheaddetails.
     *
     * @return string|null
     */
    public function getClaimheaddetails()
    {
        return $this->claimheaddetails;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return Claim
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
     * Set transfereddate.
     *
     * @param \DateTime|null $transfereddate
     *
     * @return Claim
     */
    public function setTransfereddate($transfereddate = null)
    {
        $this->transfereddate = $transfereddate;

        return $this;
    }

    /**
     * Get transfereddate.
     *
     * @return \DateTime|null
     */
    public function getTransfereddate()
    {
        return $this->transfereddate;
    }

    /**
     * Set closedon.
     *
     * @param \DateTime|null $closedon
     *
     * @return Claim
     */
    public function setClosedon($closedon = null)
    {
        $this->closedon = $closedon;

        return $this;
    }

    /**
     * Get closedon.
     *
     * @return \DateTime|null
     */
    public function getClosedon()
    {
        return $this->closedon;
    }

    /**
     * Set reviewerId.
     *
     * @param string|null $reviewerId
     *
     * @return Claim
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
     * @return Claim
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
     * Set updatedon.
     *
     * @param \DateTime|null $updatedon
     *
     * @return Claim
     */
    public function setUpdatedon($updatedon = null)
    {
        $this->updatedon = $updatedon;

        return $this;
    }

    /**
     * Get updatedon.
     *
     * @return \DateTime|null
     */
    public function getUpdatedon()
    {
        return $this->updatedon;
    }
}
