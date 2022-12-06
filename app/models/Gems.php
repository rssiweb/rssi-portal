<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Gems
 *
 * @ORM\Table(name="gems")
 * @ORM\Entity
 */
class Gems
{
    /**
     * @var string
     *
     * @ORM\Column(name="redeem_id", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="gems_redeem_id_seq", allocationSize=1, initialValue=1)
     */
    private $redeemId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_id", type="text", nullable=true)
     */
    private $userId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_name", type="text", nullable=true)
     */
    private $userName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="redeem_gems_point", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $redeemGemsPoint;

    /**
     * @var string|null
     *
     * @ORM\Column(name="redeem_type", type="text", nullable=true)
     */
    private $redeemType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_status", type="text", nullable=true)
     */
    private $reviewerStatus;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="requested_on", type="datetime", nullable=true)
     */
    private $requestedOn;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="reviewer_status_updated_on", type="datetime", nullable=true)
     */
    private $reviewerStatusUpdatedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_remarks", type="text", nullable=true)
     */
    private $reviewerRemarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_id", type="text", nullable=true)
     */
    private $reviewerId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_name", type="text", nullable=true)
     */
    private $reviewerName;



    /**
     * Get redeemId.
     *
     * @return string
     */
    public function getRedeemId()
    {
        return $this->redeemId;
    }

    /**
     * Set userId.
     *
     * @param string|null $userId
     *
     * @return Gems
     */
    public function setUserId($userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set userName.
     *
     * @param string|null $userName
     *
     * @return Gems
     */
    public function setUserName($userName = null)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set redeemGemsPoint.
     *
     * @param string|null $redeemGemsPoint
     *
     * @return Gems
     */
    public function setRedeemGemsPoint($redeemGemsPoint = null)
    {
        $this->redeemGemsPoint = $redeemGemsPoint;

        return $this;
    }

    /**
     * Get redeemGemsPoint.
     *
     * @return string|null
     */
    public function getRedeemGemsPoint()
    {
        return $this->redeemGemsPoint;
    }

    /**
     * Set redeemType.
     *
     * @param string|null $redeemType
     *
     * @return Gems
     */
    public function setRedeemType($redeemType = null)
    {
        $this->redeemType = $redeemType;

        return $this;
    }

    /**
     * Get redeemType.
     *
     * @return string|null
     */
    public function getRedeemType()
    {
        return $this->redeemType;
    }

    /**
     * Set reviewerStatus.
     *
     * @param string|null $reviewerStatus
     *
     * @return Gems
     */
    public function setReviewerStatus($reviewerStatus = null)
    {
        $this->reviewerStatus = $reviewerStatus;

        return $this;
    }

    /**
     * Get reviewerStatus.
     *
     * @return string|null
     */
    public function getReviewerStatus()
    {
        return $this->reviewerStatus;
    }

    /**
     * Set requestedOn.
     *
     * @param \DateTime|null $requestedOn
     *
     * @return Gems
     */
    public function setRequestedOn($requestedOn = null)
    {
        $this->requestedOn = $requestedOn;

        return $this;
    }

    /**
     * Get requestedOn.
     *
     * @return \DateTime|null
     */
    public function getRequestedOn()
    {
        return $this->requestedOn;
    }

    /**
     * Set reviewerStatusUpdatedOn.
     *
     * @param \DateTime|null $reviewerStatusUpdatedOn
     *
     * @return Gems
     */
    public function setReviewerStatusUpdatedOn($reviewerStatusUpdatedOn = null)
    {
        $this->reviewerStatusUpdatedOn = $reviewerStatusUpdatedOn;

        return $this;
    }

    /**
     * Get reviewerStatusUpdatedOn.
     *
     * @return \DateTime|null
     */
    public function getReviewerStatusUpdatedOn()
    {
        return $this->reviewerStatusUpdatedOn;
    }

    /**
     * Set reviewerRemarks.
     *
     * @param string|null $reviewerRemarks
     *
     * @return Gems
     */
    public function setReviewerRemarks($reviewerRemarks = null)
    {
        $this->reviewerRemarks = $reviewerRemarks;

        return $this;
    }

    /**
     * Get reviewerRemarks.
     *
     * @return string|null
     */
    public function getReviewerRemarks()
    {
        return $this->reviewerRemarks;
    }

    /**
     * Set reviewerId.
     *
     * @param string|null $reviewerId
     *
     * @return Gems
     */
    public function setReviewerId($reviewerId = null)
    {
        $this->reviewerId = $reviewerId;

        return $this;
    }

    /**
     * Get reviewerId.
     *
     * @return string|null
     */
    public function getReviewerId()
    {
        return $this->reviewerId;
    }

    /**
     * Set reviewerName.
     *
     * @param string|null $reviewerName
     *
     * @return Gems
     */
    public function setReviewerName($reviewerName = null)
    {
        $this->reviewerName = $reviewerName;

        return $this;
    }

    /**
     * Get reviewerName.
     *
     * @return string|null
     */
    public function getReviewerName()
    {
        return $this->reviewerName;
    }
}
