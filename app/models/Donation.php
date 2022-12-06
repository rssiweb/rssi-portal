<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Donation
 *
 * @ORM\Table(name="donation")
 * @ORM\Entity
 */
class Donation
{
    /**
     * @var string
     *
     * @ORM\Column(name="invoice", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="donation_invoice_seq", allocationSize=1, initialValue=1)
     */
    private $invoice;

    /**
     * @var string|null
     *
     * @ORM\Column(name="approvedby", type="text", nullable=true)
     */
    private $approvedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="profile", type="text", nullable=true)
     */
    private $profile;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mergestatus", type="text", nullable=true)
     */
    private $mergestatus;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="firstname", type="text", nullable=true)
     */
    private $firstname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="emailaddress", type="text", nullable=true)
     */
    private $emailaddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mobilenumber", type="text", nullable=true)
     */
    private $mobilenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="transactionid", type="text", nullable=true)
     */
    private $transactionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="currencyofthedonatedamount", type="text", nullable=true)
     */
    private $currencyofthedonatedamount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="additionalnote", type="text", nullable=true)
     */
    private $additionalnote;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uinumber", type="text", nullable=true)
     */
    private $uinumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uitype", type="text", nullable=true)
     */
    private $uitype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     */
    private $address;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ack", type="text", nullable=true)
     */
    private $ack;

    /**
     * @var string|null
     *
     * @ORM\Column(name="modeofpayment", type="text", nullable=true)
     */
    private $modeofpayment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cauthenticationcode", type="text", nullable=true)
     */
    private $cauthenticationcode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nameofitemsyoushared", type="text", nullable=true)
     */
    private $nameofitemsyoushared;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sauthenticationcode", type="text", nullable=true)
     */
    private $sauthenticationcode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lastname", type="text", nullable=true)
     */
    private $lastname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="youwantustospendyourdonationfor", type="text", nullable=true)
     */
    private $youwantustospendyourdonationfor;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="text", nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="text", nullable=true)
     */
    private $filename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dlastupdatedon", type="text", nullable=true)
     */
    private $dlastupdatedon;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="year", type="text", nullable=true)
     */
    private $year;

    /**
     * @var string|null
     *
     * @ORM\Column(name="donatedamount", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $donatedamount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="donation_type", type="text", nullable=true)
     */
    private $donationType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="section_code", type="text", nullable=true)
     */
    private $sectionCode;



    /**
     * Get invoice.
     *
     * @return string
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set approvedby.
     *
     * @param string|null $approvedby
     *
     * @return Donation
     */
    public function setApprovedby($approvedby = null)
    {
        $this->approvedby = $approvedby;

        return $this;
    }

    /**
     * Get approvedby.
     *
     * @return string|null
     */
    public function getApprovedby()
    {
        return $this->approvedby;
    }

    /**
     * Set profile.
     *
     * @param string|null $profile
     *
     * @return Donation
     */
    public function setProfile($profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile.
     *
     * @return string|null
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set mergestatus.
     *
     * @param string|null $mergestatus
     *
     * @return Donation
     */
    public function setMergestatus($mergestatus = null)
    {
        $this->mergestatus = $mergestatus;

        return $this;
    }

    /**
     * Get mergestatus.
     *
     * @return string|null
     */
    public function getMergestatus()
    {
        return $this->mergestatus;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return Donation
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
     * Set firstname.
     *
     * @param string|null $firstname
     *
     * @return Donation
     */
    public function setFirstname($firstname = null)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string|null
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set emailaddress.
     *
     * @param string|null $emailaddress
     *
     * @return Donation
     */
    public function setEmailaddress($emailaddress = null)
    {
        $this->emailaddress = $emailaddress;

        return $this;
    }

    /**
     * Get emailaddress.
     *
     * @return string|null
     */
    public function getEmailaddress()
    {
        return $this->emailaddress;
    }

    /**
     * Set mobilenumber.
     *
     * @param string|null $mobilenumber
     *
     * @return Donation
     */
    public function setMobilenumber($mobilenumber = null)
    {
        $this->mobilenumber = $mobilenumber;

        return $this;
    }

    /**
     * Get mobilenumber.
     *
     * @return string|null
     */
    public function getMobilenumber()
    {
        return $this->mobilenumber;
    }

    /**
     * Set transactionid.
     *
     * @param string|null $transactionid
     *
     * @return Donation
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
     * Set currencyofthedonatedamount.
     *
     * @param string|null $currencyofthedonatedamount
     *
     * @return Donation
     */
    public function setCurrencyofthedonatedamount($currencyofthedonatedamount = null)
    {
        $this->currencyofthedonatedamount = $currencyofthedonatedamount;

        return $this;
    }

    /**
     * Get currencyofthedonatedamount.
     *
     * @return string|null
     */
    public function getCurrencyofthedonatedamount()
    {
        return $this->currencyofthedonatedamount;
    }

    /**
     * Set additionalnote.
     *
     * @param string|null $additionalnote
     *
     * @return Donation
     */
    public function setAdditionalnote($additionalnote = null)
    {
        $this->additionalnote = $additionalnote;

        return $this;
    }

    /**
     * Get additionalnote.
     *
     * @return string|null
     */
    public function getAdditionalnote()
    {
        return $this->additionalnote;
    }

    /**
     * Set uinumber.
     *
     * @param string|null $uinumber
     *
     * @return Donation
     */
    public function setUinumber($uinumber = null)
    {
        $this->uinumber = $uinumber;

        return $this;
    }

    /**
     * Get uinumber.
     *
     * @return string|null
     */
    public function getUinumber()
    {
        return $this->uinumber;
    }

    /**
     * Set uitype.
     *
     * @param string|null $uitype
     *
     * @return Donation
     */
    public function setUitype($uitype = null)
    {
        $this->uitype = $uitype;

        return $this;
    }

    /**
     * Get uitype.
     *
     * @return string|null
     */
    public function getUitype()
    {
        return $this->uitype;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return Donation
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set ack.
     *
     * @param string|null $ack
     *
     * @return Donation
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
     * Set modeofpayment.
     *
     * @param string|null $modeofpayment
     *
     * @return Donation
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
     * Set cauthenticationcode.
     *
     * @param string|null $cauthenticationcode
     *
     * @return Donation
     */
    public function setCauthenticationcode($cauthenticationcode = null)
    {
        $this->cauthenticationcode = $cauthenticationcode;

        return $this;
    }

    /**
     * Get cauthenticationcode.
     *
     * @return string|null
     */
    public function getCauthenticationcode()
    {
        return $this->cauthenticationcode;
    }

    /**
     * Set nameofitemsyoushared.
     *
     * @param string|null $nameofitemsyoushared
     *
     * @return Donation
     */
    public function setNameofitemsyoushared($nameofitemsyoushared = null)
    {
        $this->nameofitemsyoushared = $nameofitemsyoushared;

        return $this;
    }

    /**
     * Get nameofitemsyoushared.
     *
     * @return string|null
     */
    public function getNameofitemsyoushared()
    {
        return $this->nameofitemsyoushared;
    }

    /**
     * Set sauthenticationcode.
     *
     * @param string|null $sauthenticationcode
     *
     * @return Donation
     */
    public function setSauthenticationcode($sauthenticationcode = null)
    {
        $this->sauthenticationcode = $sauthenticationcode;

        return $this;
    }

    /**
     * Get sauthenticationcode.
     *
     * @return string|null
     */
    public function getSauthenticationcode()
    {
        return $this->sauthenticationcode;
    }

    /**
     * Set lastname.
     *
     * @param string|null $lastname
     *
     * @return Donation
     */
    public function setLastname($lastname = null)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string|null
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set youwantustospendyourdonationfor.
     *
     * @param string|null $youwantustospendyourdonationfor
     *
     * @return Donation
     */
    public function setYouwantustospendyourdonationfor($youwantustospendyourdonationfor = null)
    {
        $this->youwantustospendyourdonationfor = $youwantustospendyourdonationfor;

        return $this;
    }

    /**
     * Get youwantustospendyourdonationfor.
     *
     * @return string|null
     */
    public function getYouwantustospendyourdonationfor()
    {
        return $this->youwantustospendyourdonationfor;
    }

    /**
     * Set code.
     *
     * @param string|null $code
     *
     * @return Donation
     */
    public function setCode($code = null)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set filename.
     *
     * @param string|null $filename
     *
     * @return Donation
     */
    public function setFilename($filename = null)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set dlastupdatedon.
     *
     * @param string|null $dlastupdatedon
     *
     * @return Donation
     */
    public function setDlastupdatedon($dlastupdatedon = null)
    {
        $this->dlastupdatedon = $dlastupdatedon;

        return $this;
    }

    /**
     * Get dlastupdatedon.
     *
     * @return string|null
     */
    public function getDlastupdatedon()
    {
        return $this->dlastupdatedon;
    }

    /**
     * Set id.
     *
     * @param int|null $id
     *
     * @return Donation
     */
    public function setId($id = null)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set year.
     *
     * @param string|null $year
     *
     * @return Donation
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
     * Set donatedamount.
     *
     * @param string|null $donatedamount
     *
     * @return Donation
     */
    public function setDonatedamount($donatedamount = null)
    {
        $this->donatedamount = $donatedamount;

        return $this;
    }

    /**
     * Get donatedamount.
     *
     * @return string|null
     */
    public function getDonatedamount()
    {
        return $this->donatedamount;
    }

    /**
     * Set donationType.
     *
     * @param string|null $donationType
     *
     * @return Donation
     */
    public function setDonationType($donationType = null)
    {
        $this->donationType = $donationType;

        return $this;
    }

    /**
     * Get donationType.
     *
     * @return string|null
     */
    public function getDonationType()
    {
        return $this->donationType;
    }

    /**
     * Set sectionCode.
     *
     * @param string|null $sectionCode
     *
     * @return Donation
     */
    public function setSectionCode($sectionCode = null)
    {
        $this->sectionCode = $sectionCode;

        return $this;
    }

    /**
     * Get sectionCode.
     *
     * @return string|null
     */
    public function getSectionCode()
    {
        return $this->sectionCode;
    }
}
