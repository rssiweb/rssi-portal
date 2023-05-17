<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Certificate
 *
 * @ORM\Table(name="certificate")
 * @ORM\Entity
 */
class Certificate
{
    /**
     * @var string
     *
     * @ORM\Column(name="certificate_no", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="certificate_certificate_no_seq", allocationSize=1, initialValue=1)
     */
    private $certificateNo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="awarded_to_id", type="text", nullable=true)
     */
    private $awardedToId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="badge_name", type="text", nullable=true)
     */
    private $badgeName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gems", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $gems;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="issuedon", type="datetime", nullable=true)
     */
    private $issuedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issuedby", type="text", nullable=true)
     */
    private $issuedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="certificate_url", type="text", nullable=true)
     */
    private $certificateUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="awarded_to_name", type="text", nullable=true)
     */
    private $awardedToName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="out_email", type="text", nullable=true)
     */
    private $outEmail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="out_phone", type="text", nullable=true)
     */
    private $outPhone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="out_scode", type="text", nullable=true)
     */
    private $outScode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="out_flag", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $outFlag;



    /**
     * Get certificateNo.
     *
     * @return string
     */
    public function getCertificateNo()
    {
        return $this->certificateNo;
    }

    /**
     * Set awardedToId.
     *
     * @param string|null $awardedToId
     *
     * @return Certificate
     */
    public function setAwardedToId($awardedToId = null)
    {
        $this->awardedToId = $awardedToId;

        return $this;
    }

    /**
     * Get awardedToId.
     *
     * @return string|null
     */
    public function getAwardedToId()
    {
        return $this->awardedToId;
    }

    /**
     * Set badgeName.
     *
     * @param string|null $badgeName
     *
     * @return Certificate
     */
    public function setBadgeName($badgeName = null)
    {
        $this->badgeName = $badgeName;

        return $this;
    }

    /**
     * Get badgeName.
     *
     * @return string|null
     */
    public function getBadgeName()
    {
        return $this->badgeName;
    }

    /**
     * Set comment.
     *
     * @param string|null $comment
     *
     * @return Certificate
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
     * Set gems.
     *
     * @param string|null $gems
     *
     * @return Certificate
     */
    public function setGems($gems = null)
    {
        $this->gems = $gems;

        return $this;
    }

    /**
     * Get gems.
     *
     * @return string|null
     */
    public function getGems()
    {
        return $this->gems;
    }

    /**
     * Set issuedon.
     *
     * @param \DateTime|null $issuedon
     *
     * @return Certificate
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

    /**
     * Set issuedby.
     *
     * @param string|null $issuedby
     *
     * @return Certificate
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
     * Set certificateUrl.
     *
     * @param string|null $certificateUrl
     *
     * @return Certificate
     */
    public function setCertificateUrl($certificateUrl = null)
    {
        $this->certificateUrl = $certificateUrl;

        return $this;
    }

    /**
     * Get certificateUrl.
     *
     * @return string|null
     */
    public function getCertificateUrl()
    {
        return $this->certificateUrl;
    }

    /**
     * Set awardedToName.
     *
     * @param string|null $awardedToName
     *
     * @return Certificate
     */
    public function setAwardedToName($awardedToName = null)
    {
        $this->awardedToName = $awardedToName;

        return $this;
    }

    /**
     * Get awardedToName.
     *
     * @return string|null
     */
    public function getAwardedToName()
    {
        return $this->awardedToName;
    }

    /**
     * Set outEmail.
     *
     * @param string|null $outEmail
     *
     * @return Certificate
     */
    public function setOutEmail($outEmail = null)
    {
        $this->outEmail = $outEmail;

        return $this;
    }

    /**
     * Get outEmail.
     *
     * @return string|null
     */
    public function getOutEmail()
    {
        return $this->outEmail;
    }

    /**
     * Set outPhone.
     *
     * @param string|null $outPhone
     *
     * @return Certificate
     */
    public function setOutPhone($outPhone = null)
    {
        $this->outPhone = $outPhone;

        return $this;
    }

    /**
     * Get outPhone.
     *
     * @return string|null
     */
    public function getOutPhone()
    {
        return $this->outPhone;
    }

    /**
     * Set outScode.
     *
     * @param string|null $outScode
     *
     * @return Certificate
     */
    public function setOutScode($outScode = null)
    {
        $this->outScode = $outScode;

        return $this;
    }

    /**
     * Get outScode.
     *
     * @return string|null
     */
    public function getOutScode()
    {
        return $this->outScode;
    }

    /**
     * Set outFlag.
     *
     * @param string|null $outFlag
     *
     * @return Certificate
     */
    public function setOutFlag($outFlag = null)
    {
        $this->outFlag = $outFlag;

        return $this;
    }

    /**
     * Get outFlag.
     *
     * @return string|null
     */
    public function getOutFlag()
    {
        return $this->outFlag;
    }
}
