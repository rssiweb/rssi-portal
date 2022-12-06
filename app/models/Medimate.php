<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Medimate
 *
 * @ORM\Table(name="medimate")
 * @ORM\Entity
 */
class Medimate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="medimate_id_seq", allocationSize=1, initialValue=1)
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
     * @ORM\Column(name="selectbeneficiary", type="text", nullable=true)
     */
    private $selectbeneficiary;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ageofbeneficiary", type="text", nullable=true)
     */
    private $ageofbeneficiary;

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
     * @ORM\Column(name="clinicname", type="text", nullable=true)
     */
    private $clinicname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="clinicpincode", type="text", nullable=true)
     */
    private $clinicpincode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="doctorregistrationno", type="text", nullable=true)
     */
    private $doctorregistrationno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nameoftreatingdoctor", type="text", nullable=true)
     */
    private $nameoftreatingdoctor;

    /**
     * @var string|null
     *
     * @ORM\Column(name="natureofillnessdiseaseaccident", type="text", nullable=true)
     */
    private $natureofillnessdiseaseaccident;

    /**
     * @var string|null
     *
     * @ORM\Column(name="treatmentstartdate", type="text", nullable=true)
     */
    private $treatmentstartdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="treatmentenddate", type="text", nullable=true)
     */
    private $treatmentenddate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="billtype", type="text", nullable=true)
     */
    private $billtype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="billnumber", type="text", nullable=true)
     */
    private $billnumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="totalbillamount", type="text", nullable=true)
     */
    private $totalbillamount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gstdlno", type="text", nullable=true)
     */
    private $gstdlno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uploadeddocuments", type="text", nullable=true)
     */
    private $uploadeddocuments;

    /**
     * @var string|null
     *
     * @ORM\Column(name="uploadeddocumentscheck", type="text", nullable=true)
     */
    private $uploadeddocumentscheck;

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
     * @ORM\Column(name="claimid", type="text", nullable=true)
     */
    private $claimid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mergestatus", type="text", nullable=true)
     */
    private $mergestatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="claimstatus", type="text", nullable=true)
     */
    private $claimstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="approvedamount", type="text", nullable=true)
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
     * @ORM\Column(name="mlastupdatedon", type="text", nullable=true)
     */
    private $mlastupdatedon;



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
     * Set timestamp.
     *
     * @param string|null $timestamp
     *
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * Set selectbeneficiary.
     *
     * @param string|null $selectbeneficiary
     *
     * @return Medimate
     */
    public function setSelectbeneficiary($selectbeneficiary = null)
    {
        $this->selectbeneficiary = $selectbeneficiary;

        return $this;
    }

    /**
     * Get selectbeneficiary.
     *
     * @return string|null
     */
    public function getSelectbeneficiary()
    {
        return $this->selectbeneficiary;
    }

    /**
     * Set ageofbeneficiary.
     *
     * @param string|null $ageofbeneficiary
     *
     * @return Medimate
     */
    public function setAgeofbeneficiary($ageofbeneficiary = null)
    {
        $this->ageofbeneficiary = $ageofbeneficiary;

        return $this;
    }

    /**
     * Get ageofbeneficiary.
     *
     * @return string|null
     */
    public function getAgeofbeneficiary()
    {
        return $this->ageofbeneficiary;
    }

    /**
     * Set bankname.
     *
     * @param string|null $bankname
     *
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * Set clinicname.
     *
     * @param string|null $clinicname
     *
     * @return Medimate
     */
    public function setClinicname($clinicname = null)
    {
        $this->clinicname = $clinicname;

        return $this;
    }

    /**
     * Get clinicname.
     *
     * @return string|null
     */
    public function getClinicname()
    {
        return $this->clinicname;
    }

    /**
     * Set clinicpincode.
     *
     * @param string|null $clinicpincode
     *
     * @return Medimate
     */
    public function setClinicpincode($clinicpincode = null)
    {
        $this->clinicpincode = $clinicpincode;

        return $this;
    }

    /**
     * Get clinicpincode.
     *
     * @return string|null
     */
    public function getClinicpincode()
    {
        return $this->clinicpincode;
    }

    /**
     * Set doctorregistrationno.
     *
     * @param string|null $doctorregistrationno
     *
     * @return Medimate
     */
    public function setDoctorregistrationno($doctorregistrationno = null)
    {
        $this->doctorregistrationno = $doctorregistrationno;

        return $this;
    }

    /**
     * Get doctorregistrationno.
     *
     * @return string|null
     */
    public function getDoctorregistrationno()
    {
        return $this->doctorregistrationno;
    }

    /**
     * Set nameoftreatingdoctor.
     *
     * @param string|null $nameoftreatingdoctor
     *
     * @return Medimate
     */
    public function setNameoftreatingdoctor($nameoftreatingdoctor = null)
    {
        $this->nameoftreatingdoctor = $nameoftreatingdoctor;

        return $this;
    }

    /**
     * Get nameoftreatingdoctor.
     *
     * @return string|null
     */
    public function getNameoftreatingdoctor()
    {
        return $this->nameoftreatingdoctor;
    }

    /**
     * Set natureofillnessdiseaseaccident.
     *
     * @param string|null $natureofillnessdiseaseaccident
     *
     * @return Medimate
     */
    public function setNatureofillnessdiseaseaccident($natureofillnessdiseaseaccident = null)
    {
        $this->natureofillnessdiseaseaccident = $natureofillnessdiseaseaccident;

        return $this;
    }

    /**
     * Get natureofillnessdiseaseaccident.
     *
     * @return string|null
     */
    public function getNatureofillnessdiseaseaccident()
    {
        return $this->natureofillnessdiseaseaccident;
    }

    /**
     * Set treatmentstartdate.
     *
     * @param string|null $treatmentstartdate
     *
     * @return Medimate
     */
    public function setTreatmentstartdate($treatmentstartdate = null)
    {
        $this->treatmentstartdate = $treatmentstartdate;

        return $this;
    }

    /**
     * Get treatmentstartdate.
     *
     * @return string|null
     */
    public function getTreatmentstartdate()
    {
        return $this->treatmentstartdate;
    }

    /**
     * Set treatmentenddate.
     *
     * @param string|null $treatmentenddate
     *
     * @return Medimate
     */
    public function setTreatmentenddate($treatmentenddate = null)
    {
        $this->treatmentenddate = $treatmentenddate;

        return $this;
    }

    /**
     * Get treatmentenddate.
     *
     * @return string|null
     */
    public function getTreatmentenddate()
    {
        return $this->treatmentenddate;
    }

    /**
     * Set billtype.
     *
     * @param string|null $billtype
     *
     * @return Medimate
     */
    public function setBilltype($billtype = null)
    {
        $this->billtype = $billtype;

        return $this;
    }

    /**
     * Get billtype.
     *
     * @return string|null
     */
    public function getBilltype()
    {
        return $this->billtype;
    }

    /**
     * Set billnumber.
     *
     * @param string|null $billnumber
     *
     * @return Medimate
     */
    public function setBillnumber($billnumber = null)
    {
        $this->billnumber = $billnumber;

        return $this;
    }

    /**
     * Get billnumber.
     *
     * @return string|null
     */
    public function getBillnumber()
    {
        return $this->billnumber;
    }

    /**
     * Set totalbillamount.
     *
     * @param string|null $totalbillamount
     *
     * @return Medimate
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
     * Set gstdlno.
     *
     * @param string|null $gstdlno
     *
     * @return Medimate
     */
    public function setGstdlno($gstdlno = null)
    {
        $this->gstdlno = $gstdlno;

        return $this;
    }

    /**
     * Get gstdlno.
     *
     * @return string|null
     */
    public function getGstdlno()
    {
        return $this->gstdlno;
    }

    /**
     * Set uploadeddocuments.
     *
     * @param string|null $uploadeddocuments
     *
     * @return Medimate
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
     * Set uploadeddocumentscheck.
     *
     * @param string|null $uploadeddocumentscheck
     *
     * @return Medimate
     */
    public function setUploadeddocumentscheck($uploadeddocumentscheck = null)
    {
        $this->uploadeddocumentscheck = $uploadeddocumentscheck;

        return $this;
    }

    /**
     * Get uploadeddocumentscheck.
     *
     * @return string|null
     */
    public function getUploadeddocumentscheck()
    {
        return $this->uploadeddocumentscheck;
    }

    /**
     * Set ack.
     *
     * @param string|null $ack
     *
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * Set claimid.
     *
     * @param string|null $claimid
     *
     * @return Medimate
     */
    public function setClaimid($claimid = null)
    {
        $this->claimid = $claimid;

        return $this;
    }

    /**
     * Get claimid.
     *
     * @return string|null
     */
    public function getClaimid()
    {
        return $this->claimid;
    }

    /**
     * Set mergestatus.
     *
     * @param string|null $mergestatus
     *
     * @return Medimate
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
     * Set claimstatus.
     *
     * @param string|null $claimstatus
     *
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * @return Medimate
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
     * Set mlastupdatedon.
     *
     * @param string|null $mlastupdatedon
     *
     * @return Medimate
     */
    public function setMlastupdatedon($mlastupdatedon = null)
    {
        $this->mlastupdatedon = $mlastupdatedon;

        return $this;
    }

    /**
     * Get mlastupdatedon.
     *
     * @return string|null
     */
    public function getMlastupdatedon()
    {
        return $this->mlastupdatedon;
    }
}
