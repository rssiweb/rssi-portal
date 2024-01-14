<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * DonationPaymentdata
 *
 * @ORM\Table(name="donation_paymentdata", indexes={@ORM\Index(name="IDX_AF5ED5B0F037AB0F", columns={"tel"})})
 * @ORM\Entity
 */
class DonationPaymentdata
{
    /**
     * @var string
     *
     * @ORM\Column(name="donationid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="donation_paymentdata_donationid_seq", allocationSize=1, initialValue=1)
     */
    private $donationid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="currency", type="string", length=50, nullable=true)
     */
    private $currency;

    /**
     * @var string|null
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="transactionid", type="text", nullable=true)
     */
    private $transactionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="modeofpayment", type="text", nullable=true)
     */
    private $modeofpayment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewedby", type="text", nullable=true)
     */
    private $reviewedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewedon", type="text", nullable=true)
     */
    private $reviewedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="text", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_remarks", type="text", nullable=true)
     */
    private $reviewerRemarks;

    /**
     * @var \DonationUserdata
     *
     * @ORM\ManyToOne(targetEntity="DonationUserdata")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tel", referencedColumnName="tel")
     * })
     */
    private $tel;



    /**
     * Get donationid.
     *
     * @return string
     */
    public function getDonationid()
    {
        return $this->donationid;
    }

    /**
     * Set currency.
     *
     * @param string|null $currency
     *
     * @return DonationPaymentdata
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
     * Set amount.
     *
     * @param string|null $amount
     *
     * @return DonationPaymentdata
     */
    public function setAmount($amount = null)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return string|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set transactionid.
     *
     * @param string|null $transactionid
     *
     * @return DonationPaymentdata
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
     * Set message.
     *
     * @param string|null $message
     *
     * @return DonationPaymentdata
     */
    public function setMessage($message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return DonationPaymentdata
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
     * Set modeofpayment.
     *
     * @param string|null $modeofpayment
     *
     * @return DonationPaymentdata
     */
    public function setModeofpayment($modeofpayment = null)
    {
        $this->modeofpayment = $modeofpayment;

        return $this;
    }

    /**
     * Get modeofpayment.
     *
     * @return string|null
     */
    public function getModeofpayment()
    {
        return $this->modeofpayment;
    }

    /**
     * Set reviewedby.
     *
     * @param string|null $reviewedby
     *
     * @return DonationPaymentdata
     */
    public function setReviewedby($reviewedby = null)
    {
        $this->reviewedby = $reviewedby;

        return $this;
    }

    /**
     * Get reviewedby.
     *
     * @return string|null
     */
    public function getReviewedby()
    {
        return $this->reviewedby;
    }

    /**
     * Set reviewedon.
     *
     * @param string|null $reviewedon
     *
     * @return DonationPaymentdata
     */
    public function setReviewedon($reviewedon = null)
    {
        $this->reviewedon = $reviewedon;

        return $this;
    }

    /**
     * Get reviewedon.
     *
     * @return string|null
     */
    public function getReviewedon()
    {
        return $this->reviewedon;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return DonationPaymentdata
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
     * Set reviewerRemarks.
     *
     * @param string|null $reviewerRemarks
     *
     * @return DonationPaymentdata
     */
    public function setReviewerRemarks($reviewerRemarks = null)
    {
        $this->reviewerRemarks = $reviewerRemarks;

        return $this;
    }

    /**
     * Get reviewerRemarks.
     *
     * @return string|null
     */
    public function getReviewerRemarks()
    {
        return $this->reviewerRemarks;
    }

    /**
     * Set tel.
     *
     * @param \DonationUserdata|null $tel
     *
     * @return DonationPaymentdata
     */
    public function setTel(\DonationUserdata $tel = null)
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * Get tel.
     *
     * @return \DonationUserdata|null
     */
    public function getTel()
    {
        return $this->tel;
    }
}
