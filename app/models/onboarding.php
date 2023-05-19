<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * onboarding
 *
 * @ORM\Table(name="onboarding")
 * @ORM\Entity
 */
class onboarding
{
    /**
     * @var string
     *
     * @ORM\Column(name="onboarding_associate_id", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="resourcemovement_onboarding_associate_id_seq", allocationSize=1, initialValue=1)
     */
    private $onboardingAssociateId;

    /**
     * @var int
     *
     * @ORM\Column(name="serial_number", type="integer", nullable=false)
     */
    private $serialNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_photo", type="text", nullable=true)
     */
    private $onboardingPhoto;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="reporting_date_time", type="datetime", nullable=true)
     */
    private $reportingDateTime;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_gen_otp_associate", type="text", nullable=true)
     */
    private $onboardingGenOtpAssociate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_otp_associate", type="text", nullable=true)
     */
    private $onboardingOtpAssociate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_gen_otp_center_incharge", type="text", nullable=true)
     */
    private $onboardingGenOtpCenterIncharge;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_otp_center_incharge", type="text", nullable=true)
     */
    private $onboardingOtpCenterIncharge;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_submitted_by", type="string", length=255, nullable=true)
     */
    private $onboardingSubmittedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="onboarding_submitted_on", type="datetime", nullable=true)
     */
    private $onboardingSubmittedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboarding_flag", type="text", nullable=true)
     */
    private $onboardingFlag;

    /**
     * @var string|null
     *
     * @ORM\Column(name="onboard_initiated_by", type="text", nullable=true)
     */
    private $onboardInitiatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="onboard_initiated_on", type="datetime", nullable=true)
     */
    private $onboardInitiatedOn;



    /**
     * Get onboardingAssociateId.
     *
     * @return string
     */
    public function getOnboardingAssociateId()
    {
        return $this->onboardingAssociateId;
    }

    /**
     * Set serialNumber.
     *
     * @param int $serialNumber
     *
     * @return onboarding
     */
    public function setSerialNumber($serialNumber)
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    /**
     * Get serialNumber.
     *
     * @return int
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * Set onboardingPhoto.
     *
     * @param string|null $onboardingPhoto
     *
     * @return onboarding
     */
    public function setOnboardingPhoto($onboardingPhoto = null)
    {
        $this->onboardingPhoto = $onboardingPhoto;

        return $this;
    }

    /**
     * Get onboardingPhoto.
     *
     * @return string|null
     */
    public function getOnboardingPhoto()
    {
        return $this->onboardingPhoto;
    }

    /**
     * Set reportingDateTime.
     *
     * @param \DateTime|null $reportingDateTime
     *
     * @return onboarding
     */
    public function setReportingDateTime($reportingDateTime = null)
    {
        $this->reportingDateTime = $reportingDateTime;

        return $this;
    }

    /**
     * Get reportingDateTime.
     *
     * @return \DateTime|null
     */
    public function getReportingDateTime()
    {
        return $this->reportingDateTime;
    }

    /**
     * Set onboardingGenOtpAssociate.
     *
     * @param string|null $onboardingGenOtpAssociate
     *
     * @return onboarding
     */
    public function setOnboardingGenOtpAssociate($onboardingGenOtpAssociate = null)
    {
        $this->onboardingGenOtpAssociate = $onboardingGenOtpAssociate;

        return $this;
    }

    /**
     * Get onboardingGenOtpAssociate.
     *
     * @return string|null
     */
    public function getOnboardingGenOtpAssociate()
    {
        return $this->onboardingGenOtpAssociate;
    }

    /**
     * Set onboardingOtpAssociate.
     *
     * @param string|null $onboardingOtpAssociate
     *
     * @return onboarding
     */
    public function setOnboardingOtpAssociate($onboardingOtpAssociate = null)
    {
        $this->onboardingOtpAssociate = $onboardingOtpAssociate;

        return $this;
    }

    /**
     * Get onboardingOtpAssociate.
     *
     * @return string|null
     */
    public function getOnboardingOtpAssociate()
    {
        return $this->onboardingOtpAssociate;
    }

    /**
     * Set onboardingGenOtpCenterIncharge.
     *
     * @param string|null $onboardingGenOtpCenterIncharge
     *
     * @return onboarding
     */
    public function setOnboardingGenOtpCenterIncharge($onboardingGenOtpCenterIncharge = null)
    {
        $this->onboardingGenOtpCenterIncharge = $onboardingGenOtpCenterIncharge;

        return $this;
    }

    /**
     * Get onboardingGenOtpCenterIncharge.
     *
     * @return string|null
     */
    public function getOnboardingGenOtpCenterIncharge()
    {
        return $this->onboardingGenOtpCenterIncharge;
    }

    /**
     * Set onboardingOtpCenterIncharge.
     *
     * @param string|null $onboardingOtpCenterIncharge
     *
     * @return onboarding
     */
    public function setOnboardingOtpCenterIncharge($onboardingOtpCenterIncharge = null)
    {
        $this->onboardingOtpCenterIncharge = $onboardingOtpCenterIncharge;

        return $this;
    }

    /**
     * Get onboardingOtpCenterIncharge.
     *
     * @return string|null
     */
    public function getOnboardingOtpCenterIncharge()
    {
        return $this->onboardingOtpCenterIncharge;
    }

    /**
     * Set onboardingSubmittedBy.
     *
     * @param string|null $onboardingSubmittedBy
     *
     * @return onboarding
     */
    public function setOnboardingSubmittedBy($onboardingSubmittedBy = null)
    {
        $this->onboardingSubmittedBy = $onboardingSubmittedBy;

        return $this;
    }

    /**
     * Get onboardingSubmittedBy.
     *
     * @return string|null
     */
    public function getOnboardingSubmittedBy()
    {
        return $this->onboardingSubmittedBy;
    }

    /**
     * Set onboardingSubmittedOn.
     *
     * @param \DateTime|null $onboardingSubmittedOn
     *
     * @return onboarding
     */
    public function setOnboardingSubmittedOn($onboardingSubmittedOn = null)
    {
        $this->onboardingSubmittedOn = $onboardingSubmittedOn;

        return $this;
    }

    /**
     * Get onboardingSubmittedOn.
     *
     * @return \DateTime|null
     */
    public function getOnboardingSubmittedOn()
    {
        return $this->onboardingSubmittedOn;
    }

    /**
     * Set onboardingFlag.
     *
     * @param string|null $onboardingFlag
     *
     * @return onboarding
     */
    public function setOnboardingFlag($onboardingFlag = null)
    {
        $this->onboardingFlag = $onboardingFlag;

        return $this;
    }

    /**
     * Get onboardingFlag.
     *
     * @return string|null
     */
    public function getOnboardingFlag()
    {
        return $this->onboardingFlag;
    }

    /**
     * Set onboardInitiatedBy.
     *
     * @param string|null $onboardInitiatedBy
     *
     * @return onboarding
     */
    public function setOnboardInitiatedBy($onboardInitiatedBy = null)
    {
        $this->onboardInitiatedBy = $onboardInitiatedBy;

        return $this;
    }

    /**
     * Get onboardInitiatedBy.
     *
     * @return string|null
     */
    public function getOnboardInitiatedBy()
    {
        return $this->onboardInitiatedBy;
    }

    /**
     * Set onboardInitiatedOn.
     *
     * @param \DateTime|null $onboardInitiatedOn
     *
     * @return onboarding
     */
    public function setOnboardInitiatedOn($onboardInitiatedOn = null)
    {
        $this->onboardInitiatedOn = $onboardInitiatedOn;

        return $this;
    }

    /**
     * Get onboardInitiatedOn.
     *
     * @return \DateTime|null
     */
    public function getOnboardInitiatedOn()
    {
        return $this->onboardInitiatedOn;
    }
}
