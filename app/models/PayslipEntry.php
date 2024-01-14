<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * PayslipEntry
 *
 * @ORM\Table(name="payslip_entry")
 * @ORM\Entity
 */
class PayslipEntry
{
    /**
     * @var string
     *
     * @ORM\Column(name="payslip_entry_id", type="string", length=255, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="payslip_entry_payslip_entry_id_seq", allocationSize=1, initialValue=1)
     */
    private $payslipEntryId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="employeeid", type="string", length=255, nullable=true)
     */
    private $employeeid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paymonth", type="string", length=255, nullable=true)
     */
    private $paymonth;

    /**
     * @var int|null
     *
     * @ORM\Column(name="payyear", type="integer", nullable=true)
     */
    private $payyear;

    /**
     * @var int|null
     *
     * @ORM\Column(name="dayspaid", type="integer", nullable=true)
     */
    private $dayspaid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="payslip_issued_by", type="text", nullable=true)
     */
    private $payslipIssuedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="payslip_issued_on", type="datetime", nullable=true)
     */
    private $payslipIssuedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="payslip_issued_ip", type="text", nullable=true)
     */
    private $payslipIssuedIp;



    /**
     * Get payslipEntryId.
     *
     * @return string
     */
    public function getPayslipEntryId()
    {
        return $this->payslipEntryId;
    }

    /**
     * Set employeeid.
     *
     * @param string|null $employeeid
     *
     * @return PayslipEntry
     */
    public function setEmployeeid($employeeid = null)
    {
        $this->employeeid = $employeeid;

        return $this;
    }

    /**
     * Get employeeid.
     *
     * @return string|null
     */
    public function getEmployeeid()
    {
        return $this->employeeid;
    }

    /**
     * Set paymonth.
     *
     * @param string|null $paymonth
     *
     * @return PayslipEntry
     */
    public function setPaymonth($paymonth = null)
    {
        $this->paymonth = $paymonth;

        return $this;
    }

    /**
     * Get paymonth.
     *
     * @return string|null
     */
    public function getPaymonth()
    {
        return $this->paymonth;
    }

    /**
     * Set payyear.
     *
     * @param int|null $payyear
     *
     * @return PayslipEntry
     */
    public function setPayyear($payyear = null)
    {
        $this->payyear = $payyear;

        return $this;
    }

    /**
     * Get payyear.
     *
     * @return int|null
     */
    public function getPayyear()
    {
        return $this->payyear;
    }

    /**
     * Set dayspaid.
     *
     * @param int|null $dayspaid
     *
     * @return PayslipEntry
     */
    public function setDayspaid($dayspaid = null)
    {
        $this->dayspaid = $dayspaid;

        return $this;
    }

    /**
     * Get dayspaid.
     *
     * @return int|null
     */
    public function getDayspaid()
    {
        return $this->dayspaid;
    }

    /**
     * Set comment.
     *
     * @param string|null $comment
     *
     * @return PayslipEntry
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
     * Set payslipIssuedBy.
     *
     * @param string|null $payslipIssuedBy
     *
     * @return PayslipEntry
     */
    public function setPayslipIssuedBy($payslipIssuedBy = null)
    {
        $this->payslipIssuedBy = $payslipIssuedBy;

        return $this;
    }

    /**
     * Get payslipIssuedBy.
     *
     * @return string|null
     */
    public function getPayslipIssuedBy()
    {
        return $this->payslipIssuedBy;
    }

    /**
     * Set payslipIssuedOn.
     *
     * @param \DateTime|null $payslipIssuedOn
     *
     * @return PayslipEntry
     */
    public function setPayslipIssuedOn($payslipIssuedOn = null)
    {
        $this->payslipIssuedOn = $payslipIssuedOn;

        return $this;
    }

    /**
     * Get payslipIssuedOn.
     *
     * @return \DateTime|null
     */
    public function getPayslipIssuedOn()
    {
        return $this->payslipIssuedOn;
    }

    /**
     * Set payslipIssuedIp.
     *
     * @param string|null $payslipIssuedIp
     *
     * @return PayslipEntry
     */
    public function setPayslipIssuedIp($payslipIssuedIp = null)
    {
        $this->payslipIssuedIp = $payslipIssuedIp;

        return $this;
    }

    /**
     * Get payslipIssuedIp.
     *
     * @return string|null
     */
    public function getPayslipIssuedIp()
    {
        return $this->payslipIssuedIp;
    }
}
