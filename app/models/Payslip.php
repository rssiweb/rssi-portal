<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Payslip
 *
 * @ORM\Table(name="payslip")
 * @ORM\Entity
 */
class Payslip
{
    /**
     * @var string
     *
     * @ORM\Column(name="payslipid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="payslip_payslipid_seq", allocationSize=1, initialValue=1)
     */
    private $payslipid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatenumber", type="text", nullable=true)
     */
    private $associatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullname", type="text", nullable=true)
     */
    private $fullname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="profile", type="text", nullable=true)
     */
    private $profile;

    /**
     * @var string|null
     *
     * @ORM\Column(name="transaction_id", type="text", nullable=true)
     */
    private $transactionId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="designation", type="text", nullable=true)
     */
    private $designation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pan", type="text", nullable=true)
     */
    private $pan;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bankname", type="text", nullable=true)
     */
    private $bankname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ifsc", type="text", nullable=true)
     */
    private $ifsc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="accno", type="text", nullable=true)
     */
    private $accno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="classcount", type="text", nullable=true)
     */
    private $classcount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="date", type="text", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sl", type="text", nullable=true)
     */
    private $sl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cl", type="text", nullable=true)
     */
    private $cl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="location", type="text", nullable=true)
     */
    private $location;

    /**
     * @var string|null
     *
     * @ORM\Column(name="basebr", type="text", nullable=true)
     */
    private $basebr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="deputebr", type="text", nullable=true)
     */
    private $deputebr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="basicsalaryar", type="text", nullable=true)
     */
    private $basicsalaryar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="basicsalarycr", type="text", nullable=true)
     */
    private $basicsalarycr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="miscar", type="text", nullable=true)
     */
    private $miscar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="misccr", type="text", nullable=true)
     */
    private $misccr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="overtimear", type="text", nullable=true)
     */
    private $overtimear;

    /**
     * @var string|null
     *
     * @ORM\Column(name="overtimecr", type="text", nullable=true)
     */
    private $overtimecr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="service_charges", type="text", nullable=true)
     */
    private $serviceCharges;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fines_penalties", type="text", nullable=true)
     */
    private $finesPenalties;

    /**
     * @var string|null
     *
     * @ORM\Column(name="totale", type="text", nullable=true)
     */
    private $totale;

    /**
     * @var string|null
     *
     * @ORM\Column(name="totald", type="text", nullable=true)
     */
    private $totald;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="text", nullable=true)
     */
    private $filename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="grade", type="text", nullable=true)
     */
    private $grade;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dateformat", type="text", nullable=true)
     */
    private $dateformat;

    /**
     * @var int|null
     *
     * @ORM\Column(name="slno", type="integer", nullable=true)
     */
    private $slno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="netpay", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $netpay;



    /**
     * Get payslipid.
     *
     * @return string
     */
    public function getPayslipid()
    {
        return $this->payslipid;
    }

    /**
     * Set associatenumber.
     *
     * @param string|null $associatenumber
     *
     * @return Payslip
     */
    public function setAssociatenumber($associatenumber = null)
    {
        $this->associatenumber = $associatenumber;

        return $this;
    }

    /**
     * Get associatenumber.
     *
     * @return string|null
     */
    public function getAssociatenumber()
    {
        return $this->associatenumber;
    }

    /**
     * Set fullname.
     *
     * @param string|null $fullname
     *
     * @return Payslip
     */
    public function setFullname($fullname = null)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string|null
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return Payslip
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
     * Set profile.
     *
     * @param string|null $profile
     *
     * @return Payslip
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
     * Set transactionId.
     *
     * @param string|null $transactionId
     *
     * @return Payslip
     */
    public function setTransactionId($transactionId = null)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * Get transactionId.
     *
     * @return string|null
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set designation.
     *
     * @param string|null $designation
     *
     * @return Payslip
     */
    public function setDesignation($designation = null)
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * Get designation.
     *
     * @return string|null
     */
    public function getDesignation()
    {
        return $this->designation;
    }

    /**
     * Set pan.
     *
     * @param string|null $pan
     *
     * @return Payslip
     */
    public function setPan($pan = null)
    {
        $this->pan = $pan;

        return $this;
    }

    /**
     * Get pan.
     *
     * @return string|null
     */
    public function getPan()
    {
        return $this->pan;
    }

    /**
     * Set bankname.
     *
     * @param string|null $bankname
     *
     * @return Payslip
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
     * Set ifsc.
     *
     * @param string|null $ifsc
     *
     * @return Payslip
     */
    public function setIfsc($ifsc = null)
    {
        $this->ifsc = $ifsc;

        return $this;
    }

    /**
     * Get ifsc.
     *
     * @return string|null
     */
    public function getIfsc()
    {
        return $this->ifsc;
    }

    /**
     * Set accno.
     *
     * @param string|null $accno
     *
     * @return Payslip
     */
    public function setAccno($accno = null)
    {
        $this->accno = $accno;

        return $this;
    }

    /**
     * Get accno.
     *
     * @return string|null
     */
    public function getAccno()
    {
        return $this->accno;
    }

    /**
     * Set classcount.
     *
     * @param string|null $classcount
     *
     * @return Payslip
     */
    public function setClasscount($classcount = null)
    {
        $this->classcount = $classcount;

        return $this;
    }

    /**
     * Get classcount.
     *
     * @return string|null
     */
    public function getClasscount()
    {
        return $this->classcount;
    }

    /**
     * Set date.
     *
     * @param string|null $date
     *
     * @return Payslip
     */
    public function setDate($date = null)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return string|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set sl.
     *
     * @param string|null $sl
     *
     * @return Payslip
     */
    public function setSl($sl = null)
    {
        $this->sl = $sl;

        return $this;
    }

    /**
     * Get sl.
     *
     * @return string|null
     */
    public function getSl()
    {
        return $this->sl;
    }

    /**
     * Set cl.
     *
     * @param string|null $cl
     *
     * @return Payslip
     */
    public function setCl($cl = null)
    {
        $this->cl = $cl;

        return $this;
    }

    /**
     * Get cl.
     *
     * @return string|null
     */
    public function getCl()
    {
        return $this->cl;
    }

    /**
     * Set location.
     *
     * @param string|null $location
     *
     * @return Payslip
     */
    public function setLocation($location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return string|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set basebr.
     *
     * @param string|null $basebr
     *
     * @return Payslip
     */
    public function setBasebr($basebr = null)
    {
        $this->basebr = $basebr;

        return $this;
    }

    /**
     * Get basebr.
     *
     * @return string|null
     */
    public function getBasebr()
    {
        return $this->basebr;
    }

    /**
     * Set deputebr.
     *
     * @param string|null $deputebr
     *
     * @return Payslip
     */
    public function setDeputebr($deputebr = null)
    {
        $this->deputebr = $deputebr;

        return $this;
    }

    /**
     * Get deputebr.
     *
     * @return string|null
     */
    public function getDeputebr()
    {
        return $this->deputebr;
    }

    /**
     * Set basicsalaryar.
     *
     * @param string|null $basicsalaryar
     *
     * @return Payslip
     */
    public function setBasicsalaryar($basicsalaryar = null)
    {
        $this->basicsalaryar = $basicsalaryar;

        return $this;
    }

    /**
     * Get basicsalaryar.
     *
     * @return string|null
     */
    public function getBasicsalaryar()
    {
        return $this->basicsalaryar;
    }

    /**
     * Set basicsalarycr.
     *
     * @param string|null $basicsalarycr
     *
     * @return Payslip
     */
    public function setBasicsalarycr($basicsalarycr = null)
    {
        $this->basicsalarycr = $basicsalarycr;

        return $this;
    }

    /**
     * Get basicsalarycr.
     *
     * @return string|null
     */
    public function getBasicsalarycr()
    {
        return $this->basicsalarycr;
    }

    /**
     * Set miscar.
     *
     * @param string|null $miscar
     *
     * @return Payslip
     */
    public function setMiscar($miscar = null)
    {
        $this->miscar = $miscar;

        return $this;
    }

    /**
     * Get miscar.
     *
     * @return string|null
     */
    public function getMiscar()
    {
        return $this->miscar;
    }

    /**
     * Set misccr.
     *
     * @param string|null $misccr
     *
     * @return Payslip
     */
    public function setMisccr($misccr = null)
    {
        $this->misccr = $misccr;

        return $this;
    }

    /**
     * Get misccr.
     *
     * @return string|null
     */
    public function getMisccr()
    {
        return $this->misccr;
    }

    /**
     * Set overtimear.
     *
     * @param string|null $overtimear
     *
     * @return Payslip
     */
    public function setOvertimear($overtimear = null)
    {
        $this->overtimear = $overtimear;

        return $this;
    }

    /**
     * Get overtimear.
     *
     * @return string|null
     */
    public function getOvertimear()
    {
        return $this->overtimear;
    }

    /**
     * Set overtimecr.
     *
     * @param string|null $overtimecr
     *
     * @return Payslip
     */
    public function setOvertimecr($overtimecr = null)
    {
        $this->overtimecr = $overtimecr;

        return $this;
    }

    /**
     * Get overtimecr.
     *
     * @return string|null
     */
    public function getOvertimecr()
    {
        return $this->overtimecr;
    }

    /**
     * Set serviceCharges.
     *
     * @param string|null $serviceCharges
     *
     * @return Payslip
     */
    public function setServiceCharges($serviceCharges = null)
    {
        $this->serviceCharges = $serviceCharges;

        return $this;
    }

    /**
     * Get serviceCharges.
     *
     * @return string|null
     */
    public function getServiceCharges()
    {
        return $this->serviceCharges;
    }

    /**
     * Set finesPenalties.
     *
     * @param string|null $finesPenalties
     *
     * @return Payslip
     */
    public function setFinesPenalties($finesPenalties = null)
    {
        $this->finesPenalties = $finesPenalties;

        return $this;
    }

    /**
     * Get finesPenalties.
     *
     * @return string|null
     */
    public function getFinesPenalties()
    {
        return $this->finesPenalties;
    }

    /**
     * Set totale.
     *
     * @param string|null $totale
     *
     * @return Payslip
     */
    public function setTotale($totale = null)
    {
        $this->totale = $totale;

        return $this;
    }

    /**
     * Get totale.
     *
     * @return string|null
     */
    public function getTotale()
    {
        return $this->totale;
    }

    /**
     * Set totald.
     *
     * @param string|null $totald
     *
     * @return Payslip
     */
    public function setTotald($totald = null)
    {
        $this->totald = $totald;

        return $this;
    }

    /**
     * Get totald.
     *
     * @return string|null
     */
    public function getTotald()
    {
        return $this->totald;
    }

    /**
     * Set filename.
     *
     * @param string|null $filename
     *
     * @return Payslip
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
     * Set grade.
     *
     * @param string|null $grade
     *
     * @return Payslip
     */
    public function setGrade($grade = null)
    {
        $this->grade = $grade;

        return $this;
    }

    /**
     * Get grade.
     *
     * @return string|null
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Set dateformat.
     *
     * @param string|null $dateformat
     *
     * @return Payslip
     */
    public function setDateformat($dateformat = null)
    {
        $this->dateformat = $dateformat;

        return $this;
    }

    /**
     * Get dateformat.
     *
     * @return string|null
     */
    public function getDateformat()
    {
        return $this->dateformat;
    }

    /**
     * Set slno.
     *
     * @param int|null $slno
     *
     * @return Payslip
     */
    public function setSlno($slno = null)
    {
        $this->slno = $slno;

        return $this;
    }

    /**
     * Get slno.
     *
     * @return int|null
     */
    public function getSlno()
    {
        return $this->slno;
    }

    /**
     * Set netpay.
     *
     * @param string|null $netpay
     *
     * @return Payslip
     */
    public function setNetpay($netpay = null)
    {
        $this->netpay = $netpay;

        return $this;
    }

    /**
     * Get netpay.
     *
     * @return string|null
     */
    public function getNetpay()
    {
        return $this->netpay;
    }
}
