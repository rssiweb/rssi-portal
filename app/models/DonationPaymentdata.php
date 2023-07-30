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
     * @ORM\Column(name="transactionid", type="string", length=50, nullable=true)
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
