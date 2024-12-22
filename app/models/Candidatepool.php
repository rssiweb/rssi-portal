<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * talent_pool
 *
 * @ORM\Table(name="talent_pool")
 * @ORM\Entity
 */
class talent_pool
{
    /**
     * @var string
     *
     * @ORM\Column(name="application_number", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="candidatepool_application_number_seq", allocationSize=1, initialValue=1)
     */
    private $applicationNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="applicant_f_name", type="text", nullable=true)
     */
    private $applicantFName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="applicant_l_name", type="text", nullable=true)
     */
    private $applicantLName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="national_identifier", type="text", nullable=true)
     */
    private $nationalIdentifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="national_identifier_number", type="text", nullable=true)
     */
    private $nationalIdentifierNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contact", type="text", nullable=true)
     */
    private $contact;

    /**
     * @var string|null
     *
     * @ORM\Column(name="base_branch", type="text", nullable=true)
     */
    private $baseBranch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="association_type", type="text", nullable=true)
     */
    private $associationType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="supporting_document", type="text", nullable=true)
     */
    private $supportingDocument;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cv", type="text", nullable=true)
     */
    private $cv;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="appliedon", type="date", nullable=true)
     */
    private $appliedon;



    /**
     * Get applicationNumber.
     *
     * @return string
     */
    public function getApplicationNumber()
    {
        return $this->applicationNumber;
    }

    /**
     * Set applicantFName.
     *
     * @param string|null $applicantFName
     *
     * @return talent_pool
     */
    public function setApplicantFName($applicantFName = null)
    {
        $this->applicantFName = $applicantFName;

        return $this;
    }

    /**
     * Get applicantFName.
     *
     * @return string|null
     */
    public function getApplicantFName()
    {
        return $this->applicantFName;
    }

    /**
     * Set applicantLName.
     *
     * @param string|null $applicantLName
     *
     * @return talent_pool
     */
    public function setApplicantLName($applicantLName = null)
    {
        $this->applicantLName = $applicantLName;

        return $this;
    }

    /**
     * Get applicantLName.
     *
     * @return string|null
     */
    public function getApplicantLName()
    {
        return $this->applicantLName;
    }

    /**
     * Set nationalIdentifier.
     *
     * @param string|null $nationalIdentifier
     *
     * @return talent_pool
     */
    public function setNationalIdentifier($nationalIdentifier = null)
    {
        $this->nationalIdentifier = $nationalIdentifier;

        return $this;
    }

    /**
     * Get nationalIdentifier.
     *
     * @return string|null
     */
    public function getNationalIdentifier()
    {
        return $this->nationalIdentifier;
    }

    /**
     * Set nationalIdentifierNumber.
     *
     * @param string|null $nationalIdentifierNumber
     *
     * @return talent_pool
     */
    public function setNationalIdentifierNumber($nationalIdentifierNumber = null)
    {
        $this->nationalIdentifierNumber = $nationalIdentifierNumber;

        return $this;
    }

    /**
     * Get nationalIdentifierNumber.
     *
     * @return string|null
     */
    public function getNationalIdentifierNumber()
    {
        return $this->nationalIdentifierNumber;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return talent_pool
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
     * Set contact.
     *
     * @param string|null $contact
     *
     * @return talent_pool
     */
    public function setContact($contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return string|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set baseBranch.
     *
     * @param string|null $baseBranch
     *
     * @return talent_pool
     */
    public function setBaseBranch($baseBranch = null)
    {
        $this->baseBranch = $baseBranch;

        return $this;
    }

    /**
     * Get baseBranch.
     *
     * @return string|null
     */
    public function getBaseBranch()
    {
        return $this->baseBranch;
    }

    /**
     * Set associationType.
     *
     * @param string|null $associationType
     *
     * @return talent_pool
     */
    public function setAssociationType($associationType = null)
    {
        $this->associationType = $associationType;

        return $this;
    }

    /**
     * Get associationType.
     *
     * @return string|null
     */
    public function getAssociationType()
    {
        return $this->associationType;
    }

    /**
     * Set supportingDocument.
     *
     * @param string|null $supportingDocument
     *
     * @return talent_pool
     */
    public function setSupportingDocument($supportingDocument = null)
    {
        $this->supportingDocument = $supportingDocument;

        return $this;
    }

    /**
     * Get supportingDocument.
     *
     * @return string|null
     */
    public function getSupportingDocument()
    {
        return $this->supportingDocument;
    }

    /**
     * Set cv.
     *
     * @param string|null $cv
     *
     * @return talent_pool
     */
    public function setCv($cv = null)
    {
        $this->cv = $cv;

        return $this;
    }

    /**
     * Get cv.
     *
     * @return string|null
     */
    public function getCv()
    {
        return $this->cv;
    }

    /**
     * Set appliedon.
     *
     * @param \DateTime|null $appliedon
     *
     * @return talent_pool
     */
    public function setAppliedon($appliedon = null)
    {
        $this->appliedon = $appliedon;

        return $this;
    }

    /**
     * Get appliedon.
     *
     * @return \DateTime|null
     */
    public function getAppliedon()
    {
        return $this->appliedon;
    }
}
