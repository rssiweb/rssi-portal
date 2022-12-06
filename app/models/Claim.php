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
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="timestamp", type="text", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="registrationid", type="text", nullable=true)
     */
    private $registrationid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mobilenumber", type="text", nullable=true)
     */
    private $mobilenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bankname", type="text", nullable=true)
     */
    private $bankname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="accountnumber", type="text", nullable=true)
     */
    private $accountnumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="accountholdername", type="text", nullable=true)
     */
    private $accountholdername;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ifsccode", type="text", nullable=true)
     */
    private $ifsccode;

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
     * @ORM\Column(name="termsofagreement", type="text", nullable=true)
     */
    private $termsofagreement;

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
     * @ORM\Column(name="transfereddate", type="text", nullable=true)
     */
    private $transfereddate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="closedon", type="text", nullable=true)
     */
    private $closedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mediremarks", type="text", nullable=true)
     */
    private $mediremarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="profile", type="text", nullable=true)
     */
    private $profile;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rlastupdatedon", type="text", nullable=true)
     */
    private $rlastupdatedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="claimheaddetails", type="text", nullable=true)
     */
    private $claimheaddetails;



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
     * Set id.
     *
     * @param int|null $id
     *
     * @return Claim
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
     * Set timestamp.
     *
     * @param string|null $timestamp
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
     * @return string|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return Claim
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
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
     * Set mobilenumber.
     *
     * @param string|null $mobilenumber
     *
     * @return Claim
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
     * Set email.
     *
     * @param string|null $email
     *
     * @return Claim
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set bankname.
     *
     * @param string|null $bankname
     *
     * @return Claim
     */
    public function setBankname($bankname = null)
    {
        $this->bankname = $bankname;

        return $this;
    }

    /**
     * Get bankname.
     *
     * @return string|null
     */
    public function getBankname()
    {
        return $this->bankname;
    }

    /**
     * Set accountnumber.
     *
     * @param string|null $accountnumber
     *
     * @return Claim
     */
    public function setAccountnumber($accountnumber = null)
    {
        $this->accountnumber = $accountnumber;

        return $this;
    }

    /**
     * Get accountnumber.
     *
     * @return string|null
     */
    public function getAccountnumber()
    {
        return $this->accountnumber;
    }

    /**
     * Set accountholdername.
     *
     * @param string|null $accountholdername
     *
     * @return Claim
     */
    public function setAccountholdername($accountholdername = null)
    {
        $this->accountholdername = $accountholdername;

        return $this;
    }

    /**
     * Get accountholdername.
     *
     * @return string|null
     */
    public function getAccountholdername()
    {
        return $this->accountholdername;
    }

    /**
     * Set ifsccode.
     *
     * @param string|null $ifsccode
     *
     * @return Claim
     */
    public function setIfsccode($ifsccode = null)
    {
        $this->ifsccode = $ifsccode;

        return $this;
    }

    /**
     * Get ifsccode.
     *
     * @return string|null
     */
    public function getIfsccode()
    {
        return $this->ifsccode;
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
     * Set termsofagreement.
     *
     * @param string|null $termsofagreement
     *
     * @return Claim
     */
    public function setTermsofagreement($termsofagreement = null)
    {
        $this->termsofagreement = $termsofagreement;

        return $this;
    }

    /**
     * Get termsofagreement.
     *
     * @return string|null
     */
    public function getTermsofagreement()
    {
        return $this->termsofagreement;
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
     * Set transfereddate.
     *
     * @param string|null $transfereddate
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
     * @return string|null
     */
    public function getTransfereddate()
    {
        return $this->transfereddate;
    }

    /**
     * Set closedon.
     *
     * @param string|null $closedon
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
     * @return string|null
     */
    public function getClosedon()
    {
        return $this->closedon;
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
     * Set profile.
     *
     * @param string|null $profile
     *
     * @return Claim
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
     * Set rlastupdatedon.
     *
     * @param string|null $rlastupdatedon
     *
     * @return Claim
     */
    public function setRlastupdatedon($rlastupdatedon = null)
    {
        $this->rlastupdatedon = $rlastupdatedon;

        return $this;
    }

    /**
     * Get rlastupdatedon.
     *
     * @return string|null
     */
    public function getRlastupdatedon()
    {
        return $this->rlastupdatedon;
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
}
