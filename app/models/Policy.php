<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Policy
 *
 * @ORM\Table(name="policy")
 * @ORM\Entity
 */
class Policy
{
    /**
     * @var string
     *
     * @ORM\Column(name="policyid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="policy_policyid_seq", allocationSize=1, initialValue=1)
     */
    private $policyid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="policyname", type="text", nullable=true)
     */
    private $policyname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="policydoc", type="text", nullable=true)
     */
    private $policydoc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="policytype", type="text", nullable=true)
     */
    private $policytype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issuedby", type="text", nullable=true)
     */
    private $issuedby;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="issuedon", type="datetime", nullable=true)
     */
    private $issuedon;



    /**
     * Get policyid.
     *
     * @return string
     */
    public function getPolicyid()
    {
        return $this->policyid;
    }

    /**
     * Set policyname.
     *
     * @param string|null $policyname
     *
     * @return Policy
     */
    public function setPolicyname($policyname = null)
    {
        $this->policyname = $policyname;

        return $this;
    }

    /**
     * Get policyname.
     *
     * @return string|null
     */
    public function getPolicyname()
    {
        return $this->policyname;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return Policy
     */
    public function setRemarks($remarks = null)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string|null
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set policydoc.
     *
     * @param string|null $policydoc
     *
     * @return Policy
     */
    public function setPolicydoc($policydoc = null)
    {
        $this->policydoc = $policydoc;

        return $this;
    }

    /**
     * Get policydoc.
     *
     * @return string|null
     */
    public function getPolicydoc()
    {
        return $this->policydoc;
    }

    /**
     * Set policytype.
     *
     * @param string|null $policytype
     *
     * @return Policy
     */
    public function setPolicytype($policytype = null)
    {
        $this->policytype = $policytype;

        return $this;
    }

    /**
     * Get policytype.
     *
     * @return string|null
     */
    public function getPolicytype()
    {
        return $this->policytype;
    }

    /**
     * Set issuedby.
     *
     * @param string|null $issuedby
     *
     * @return Policy
     */
    public function setIssuedby($issuedby = null)
    {
        $this->issuedby = $issuedby;

        return $this;
    }

    /**
     * Get issuedby.
     *
     * @return string|null
     */
    public function getIssuedby()
    {
        return $this->issuedby;
    }

    /**
     * Set issuedon.
     *
     * @param \DateTime|null $issuedon
     *
     * @return Policy
     */
    public function setIssuedon($issuedon = null)
    {
        $this->issuedon = $issuedon;

        return $this;
    }

    /**
     * Get issuedon.
     *
     * @return \DateTime|null
     */
    public function getIssuedon()
    {
        return $this->issuedon;
    }
}
