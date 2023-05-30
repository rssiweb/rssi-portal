<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * AssociateExit
 *
 * @ORM\Table(name="associate_exit")
 * @ORM\Entity
 */
class AssociateExit
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="associate_exit_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_associate_id", type="string", nullable=true)
     */
    private $exitAssociateId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_photo", type="text", nullable=true)
     */
    private $exitPhoto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="asset_clearance", type="boolean", nullable=true)
     */
    private $assetClearance;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="financial_clearance", type="boolean", nullable=true)
     */
    private $financialClearance;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="security_clearance", type="boolean", nullable=true)
     */
    private $securityClearance;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="hr_clearance", type="boolean", nullable=true)
     */
    private $hrClearance;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="work_clearance", type="boolean", nullable=true)
     */
    private $workClearance;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="legal_clearance", type="boolean", nullable=true)
     */
    private $legalClearance;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_interview", type="text", nullable=true)
     */
    private $exitInterview;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_initiated_by", type="string", nullable=true)
     */
    private $exitInitiatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="exit_initiated_on", type="datetime", nullable=true)
     */
    private $exitInitiatedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_submitted_by", type="string", nullable=true)
     */
    private $exitSubmittedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="exit_submitted_on", type="datetime", nullable=true)
     */
    private $exitSubmittedOn;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="exit_date_time", type="datetime", nullable=true)
     */
    private $exitDateTime;

    /**
     * @var string|null
     *
     * @ORM\Column(name="otp_associate", type="text", nullable=true)
     */
    private $otpAssociate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="otp_center_incharge", type="text", nullable=true)
     */
    private $otpCenterIncharge;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_gen_otp_associate", type="text", nullable=true)
     */
    private $exitGenOtpAssociate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_gen_otp_center_incharge", type="text", nullable=true)
     */
    private $exitGenOtpCenterIncharge;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exit_flag", type="text", nullable=true)
     */
    private $exitFlag;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_address", type="string", nullable=true)
     */
    private $ipAddress;



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
     * Set exitAssociateId.
     *
     * @param string|null $exitAssociateId
     *
     * @return AssociateExit
     */
    public function setExitAssociateId($exitAssociateId = null)
    {
        $this->exitAssociateId = $exitAssociateId;

        return $this;
    }

    /**
     * Get exitAssociateId.
     *
     * @return string|null
     */
    public function getExitAssociateId()
    {
        return $this->exitAssociateId;
    }

    /**
     * Set exitPhoto.
     *
     * @param string|null $exitPhoto
     *
     * @return AssociateExit
     */
    public function setExitPhoto($exitPhoto = null)
    {
        $this->exitPhoto = $exitPhoto;

        return $this;
    }

    /**
     * Get exitPhoto.
     *
     * @return string|null
     */
    public function getExitPhoto()
    {
        return $this->exitPhoto;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return AssociateExit
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
     * Set assetClearance.
     *
     * @param bool|null $assetClearance
     *
     * @return AssociateExit
     */
    public function setAssetClearance($assetClearance = null)
    {
        $this->assetClearance = $assetClearance;

        return $this;
    }

    /**
     * Get assetClearance.
     *
     * @return bool|null
     */
    public function getAssetClearance()
    {
        return $this->assetClearance;
    }

    /**
     * Set financialClearance.
     *
     * @param bool|null $financialClearance
     *
     * @return AssociateExit
     */
    public function setFinancialClearance($financialClearance = null)
    {
        $this->financialClearance = $financialClearance;

        return $this;
    }

    /**
     * Get financialClearance.
     *
     * @return bool|null
     */
    public function getFinancialClearance()
    {
        return $this->financialClearance;
    }

    /**
     * Set securityClearance.
     *
     * @param bool|null $securityClearance
     *
     * @return AssociateExit
     */
    public function setSecurityClearance($securityClearance = null)
    {
        $this->securityClearance = $securityClearance;

        return $this;
    }

    /**
     * Get securityClearance.
     *
     * @return bool|null
     */
    public function getSecurityClearance()
    {
        return $this->securityClearance;
    }

    /**
     * Set hrClearance.
     *
     * @param bool|null $hrClearance
     *
     * @return AssociateExit
     */
    public function setHrClearance($hrClearance = null)
    {
        $this->hrClearance = $hrClearance;

        return $this;
    }

    /**
     * Get hrClearance.
     *
     * @return bool|null
     */
    public function getHrClearance()
    {
        return $this->hrClearance;
    }

    /**
     * Set workClearance.
     *
     * @param bool|null $workClearance
     *
     * @return AssociateExit
     */
    public function setWorkClearance($workClearance = null)
    {
        $this->workClearance = $workClearance;

        return $this;
    }

    /**
     * Get workClearance.
     *
     * @return bool|null
     */
    public function getWorkClearance()
    {
        return $this->workClearance;
    }

    /**
     * Set legalClearance.
     *
     * @param bool|null $legalClearance
     *
     * @return AssociateExit
     */
    public function setLegalClearance($legalClearance = null)
    {
        $this->legalClearance = $legalClearance;

        return $this;
    }

    /**
     * Get legalClearance.
     *
     * @return bool|null
     */
    public function getLegalClearance()
    {
        return $this->legalClearance;
    }

    /**
     * Set exitInterview.
     *
     * @param string|null $exitInterview
     *
     * @return AssociateExit
     */
    public function setExitInterview($exitInterview = null)
    {
        $this->exitInterview = $exitInterview;

        return $this;
    }

    /**
     * Get exitInterview.
     *
     * @return string|null
     */
    public function getExitInterview()
    {
        return $this->exitInterview;
    }

    /**
     * Set exitInitiatedBy.
     *
     * @param string|null $exitInitiatedBy
     *
     * @return AssociateExit
     */
    public function setExitInitiatedBy($exitInitiatedBy = null)
    {
        $this->exitInitiatedBy = $exitInitiatedBy;

        return $this;
    }

    /**
     * Get exitInitiatedBy.
     *
     * @return string|null
     */
    public function getExitInitiatedBy()
    {
        return $this->exitInitiatedBy;
    }

    /**
     * Set exitInitiatedOn.
     *
     * @param \DateTime|null $exitInitiatedOn
     *
     * @return AssociateExit
     */
    public function setExitInitiatedOn($exitInitiatedOn = null)
    {
        $this->exitInitiatedOn = $exitInitiatedOn;

        return $this;
    }

    /**
     * Get exitInitiatedOn.
     *
     * @return \DateTime|null
     */
    public function getExitInitiatedOn()
    {
        return $this->exitInitiatedOn;
    }

    /**
     * Set exitSubmittedBy.
     *
     * @param string|null $exitSubmittedBy
     *
     * @return AssociateExit
     */
    public function setExitSubmittedBy($exitSubmittedBy = null)
    {
        $this->exitSubmittedBy = $exitSubmittedBy;

        return $this;
    }

    /**
     * Get exitSubmittedBy.
     *
     * @return string|null
     */
    public function getExitSubmittedBy()
    {
        return $this->exitSubmittedBy;
    }

    /**
     * Set exitSubmittedOn.
     *
     * @param \DateTime|null $exitSubmittedOn
     *
     * @return AssociateExit
     */
    public function setExitSubmittedOn($exitSubmittedOn = null)
    {
        $this->exitSubmittedOn = $exitSubmittedOn;

        return $this;
    }

    /**
     * Get exitSubmittedOn.
     *
     * @return \DateTime|null
     */
    public function getExitSubmittedOn()
    {
        return $this->exitSubmittedOn;
    }

    /**
     * Set exitDateTime.
     *
     * @param \DateTime|null $exitDateTime
     *
     * @return AssociateExit
     */
    public function setExitDateTime($exitDateTime = null)
    {
        $this->exitDateTime = $exitDateTime;

        return $this;
    }

    /**
     * Get exitDateTime.
     *
     * @return \DateTime|null
     */
    public function getExitDateTime()
    {
        return $this->exitDateTime;
    }

    /**
     * Set otpAssociate.
     *
     * @param string|null $otpAssociate
     *
     * @return AssociateExit
     */
    public function setOtpAssociate($otpAssociate = null)
    {
        $this->otpAssociate = $otpAssociate;

        return $this;
    }

    /**
     * Get otpAssociate.
     *
     * @return string|null
     */
    public function getOtpAssociate()
    {
        return $this->otpAssociate;
    }

    /**
     * Set otpCenterIncharge.
     *
     * @param string|null $otpCenterIncharge
     *
     * @return AssociateExit
     */
    public function setOtpCenterIncharge($otpCenterIncharge = null)
    {
        $this->otpCenterIncharge = $otpCenterIncharge;

        return $this;
    }

    /**
     * Get otpCenterIncharge.
     *
     * @return string|null
     */
    public function getOtpCenterIncharge()
    {
        return $this->otpCenterIncharge;
    }

    /**
     * Set exitGenOtpAssociate.
     *
     * @param string|null $exitGenOtpAssociate
     *
     * @return AssociateExit
     */
    public function setExitGenOtpAssociate($exitGenOtpAssociate = null)
    {
        $this->exitGenOtpAssociate = $exitGenOtpAssociate;

        return $this;
    }

    /**
     * Get exitGenOtpAssociate.
     *
     * @return string|null
     */
    public function getExitGenOtpAssociate()
    {
        return $this->exitGenOtpAssociate;
    }

    /**
     * Set exitGenOtpCenterIncharge.
     *
     * @param string|null $exitGenOtpCenterIncharge
     *
     * @return AssociateExit
     */
    public function setExitGenOtpCenterIncharge($exitGenOtpCenterIncharge = null)
    {
        $this->exitGenOtpCenterIncharge = $exitGenOtpCenterIncharge;

        return $this;
    }

    /**
     * Get exitGenOtpCenterIncharge.
     *
     * @return string|null
     */
    public function getExitGenOtpCenterIncharge()
    {
        return $this->exitGenOtpCenterIncharge;
    }

    /**
     * Set exitFlag.
     *
     * @param string|null $exitFlag
     *
     * @return AssociateExit
     */
    public function setExitFlag($exitFlag = null)
    {
        $this->exitFlag = $exitFlag;

        return $this;
    }

    /**
     * Get exitFlag.
     *
     * @return string|null
     */
    public function getExitFlag()
    {
        return $this->exitFlag;
    }

    /**
     * Set ipAddress.
     *
     * @param string|null $ipAddress
     *
     * @return AssociateExit
     */
    public function setIpAddress($ipAddress = null)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }
}
