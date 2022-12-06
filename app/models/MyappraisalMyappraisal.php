<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * MyappraisalMyappraisal
 *
 * @ORM\Table(name="myappraisal_myappraisal")
 * @ORM\Entity
 */
class MyappraisalMyappraisal
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="myappraisal_myappraisal_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisaltype", type="string", length=512, nullable=true)
     */
    private $appraisaltype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatenumber", type="string", length=512, nullable=true)
     */
    private $associatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullname", type="string", length=512, nullable=true)
     */
    private $fullname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="effectivestartdate", type="string", length=512, nullable=true)
     */
    private $effectivestartdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="effectiveenddate", type="string", length=512, nullable=true)
     */
    private $effectiveenddate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="role", type="string", length=512, nullable=true)
     */
    private $role;

    /**
     * @var string|null
     *
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    private $feedback;

    /**
     * @var string|null
     *
     * @ORM\Column(name="scopeofimprovement", type="string", length=1024, nullable=true)
     */
    private $scopeofimprovement;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf", type="string", length=512, nullable=true)
     */
    private $ipf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="flag", type="string", length=512, nullable=true)
     */
    private $flag;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filter", type="text", nullable=true)
     */
    private $filter;



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
     * Set appraisaltype.
     *
     * @param string|null $appraisaltype
     *
     * @return MyappraisalMyappraisal
     */
    public function setAppraisaltype($appraisaltype = null)
    {
        $this->appraisaltype = $appraisaltype;

        return $this;
    }

    /**
     * Get appraisaltype.
     *
     * @return string|null
     */
    public function getAppraisaltype()
    {
        return $this->appraisaltype;
    }

    /**
     * Set associatenumber.
     *
     * @param string|null $associatenumber
     *
     * @return MyappraisalMyappraisal
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
     * @return MyappraisalMyappraisal
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
     * Set effectivestartdate.
     *
     * @param string|null $effectivestartdate
     *
     * @return MyappraisalMyappraisal
     */
    public function setEffectivestartdate($effectivestartdate = null)
    {
        $this->effectivestartdate = $effectivestartdate;

        return $this;
    }

    /**
     * Get effectivestartdate.
     *
     * @return string|null
     */
    public function getEffectivestartdate()
    {
        return $this->effectivestartdate;
    }

    /**
     * Set effectiveenddate.
     *
     * @param string|null $effectiveenddate
     *
     * @return MyappraisalMyappraisal
     */
    public function setEffectiveenddate($effectiveenddate = null)
    {
        $this->effectiveenddate = $effectiveenddate;

        return $this;
    }

    /**
     * Get effectiveenddate.
     *
     * @return string|null
     */
    public function getEffectiveenddate()
    {
        return $this->effectiveenddate;
    }

    /**
     * Set role.
     *
     * @param string|null $role
     *
     * @return MyappraisalMyappraisal
     */
    public function setRole($role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return string|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set feedback.
     *
     * @param string|null $feedback
     *
     * @return MyappraisalMyappraisal
     */
    public function setFeedback($feedback = null)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback.
     *
     * @return string|null
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set scopeofimprovement.
     *
     * @param string|null $scopeofimprovement
     *
     * @return MyappraisalMyappraisal
     */
    public function setScopeofimprovement($scopeofimprovement = null)
    {
        $this->scopeofimprovement = $scopeofimprovement;

        return $this;
    }

    /**
     * Get scopeofimprovement.
     *
     * @return string|null
     */
    public function getScopeofimprovement()
    {
        return $this->scopeofimprovement;
    }

    /**
     * Set ipf.
     *
     * @param string|null $ipf
     *
     * @return MyappraisalMyappraisal
     */
    public function setIpf($ipf = null)
    {
        $this->ipf = $ipf;

        return $this;
    }

    /**
     * Get ipf.
     *
     * @return string|null
     */
    public function getIpf()
    {
        return $this->ipf;
    }

    /**
     * Set flag.
     *
     * @param string|null $flag
     *
     * @return MyappraisalMyappraisal
     */
    public function setFlag($flag = null)
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * Get flag.
     *
     * @return string|null
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * Set filter.
     *
     * @param string|null $filter
     *
     * @return MyappraisalMyappraisal
     */
    public function setFilter($filter = null)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter.
     *
     * @return string|null
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
