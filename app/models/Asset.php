<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Asset
 *
 * @ORM\Table(name="asset")
 * @ORM\Entity
 */
class Asset
{
    /**
     * @var string
     *
     * @ORM\Column(name="submissionid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="asset_submissionid_seq", allocationSize=1, initialValue=1)
     */
    private $submissionid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="userid", type="text", nullable=true)
     */
    private $userid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="usertype", type="text", nullable=true)
     */
    private $usertype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="assetdetails", type="text", nullable=true)
     */
    private $assetdetails;

    /**
     * @var string|null
     *
     * @ORM\Column(name="agreement", type="text", nullable=true)
     */
    private $agreement;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issuedon", type="text", nullable=true)
     */
    private $issuedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="returnedon", type="text", nullable=true)
     */
    private $returnedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="receivedon", type="text", nullable=true)
     */
    private $receivedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="text", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string|null
     *
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="agreementname", type="text", nullable=true)
     */
    private $agreementname;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;



    /**
     * Get submissionid.
     *
     * @return string
     */
    public function getSubmissionid()
    {
        return $this->submissionid;
    }

    /**
     * Set userid.
     *
     * @param string|null $userid
     *
     * @return Asset
     */
    public function setUserid($userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return string|null
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set usertype.
     *
     * @param string|null $usertype
     *
     * @return Asset
     */
    public function setUsertype($usertype = null)
    {
        $this->usertype = $usertype;

        return $this;
    }

    /**
     * Get usertype.
     *
     * @return string|null
     */
    public function getUsertype()
    {
        return $this->usertype;
    }

    /**
     * Set assetdetails.
     *
     * @param string|null $assetdetails
     *
     * @return Asset
     */
    public function setAssetdetails($assetdetails = null)
    {
        $this->assetdetails = $assetdetails;

        return $this;
    }

    /**
     * Get assetdetails.
     *
     * @return string|null
     */
    public function getAssetdetails()
    {
        return $this->assetdetails;
    }

    /**
     * Set agreement.
     *
     * @param string|null $agreement
     *
     * @return Asset
     */
    public function setAgreement($agreement = null)
    {
        $this->agreement = $agreement;

        return $this;
    }

    /**
     * Get agreement.
     *
     * @return string|null
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * Set issuedon.
     *
     * @param string|null $issuedon
     *
     * @return Asset
     */
    public function setIssuedon($issuedon = null)
    {
        $this->issuedon = $issuedon;

        return $this;
    }

    /**
     * Get issuedon.
     *
     * @return string|null
     */
    public function getIssuedon()
    {
        return $this->issuedon;
    }

    /**
     * Set returnedon.
     *
     * @param string|null $returnedon
     *
     * @return Asset
     */
    public function setReturnedon($returnedon = null)
    {
        $this->returnedon = $returnedon;

        return $this;
    }

    /**
     * Get returnedon.
     *
     * @return string|null
     */
    public function getReturnedon()
    {
        return $this->returnedon;
    }

    /**
     * Set receivedon.
     *
     * @param string|null $receivedon
     *
     * @return Asset
     */
    public function setReceivedon($receivedon = null)
    {
        $this->receivedon = $receivedon;

        return $this;
    }

    /**
     * Get receivedon.
     *
     * @return string|null
     */
    public function getReceivedon()
    {
        return $this->receivedon;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return Asset
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
     * Set comment.
     *
     * @param string|null $comment
     *
     * @return Asset
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
     * Set category.
     *
     * @param string|null $category
     *
     * @return Asset
     */
    public function setCategory($category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return string|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set agreementname.
     *
     * @param string|null $agreementname
     *
     * @return Asset
     */
    public function setAgreementname($agreementname = null)
    {
        $this->agreementname = $agreementname;

        return $this;
    }

    /**
     * Get agreementname.
     *
     * @return string|null
     */
    public function getAgreementname()
    {
        return $this->agreementname;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return Asset
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
}
