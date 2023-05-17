<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Student
 *
 * @ORM\Table(name="student")
 * @ORM\Entity
 */
class Student
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="student_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type_of_admission", type="text", nullable=true)
     */
    private $typeOfAdmission;

    /**
     * @var string|null
     *
     * @ORM\Column(name="student_name", type="text", nullable=true)
     */
    private $studentName;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_of_birth", type="date", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gender", type="text", nullable=true)
     */
    private $gender;

    /**
     * @var string|null
     *
     * @ORM\Column(name="student_photo", type="text", nullable=true)
     */
    private $studentPhoto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="aadhar_available", type="text", nullable=true)
     */
    private $aadharAvailable;

    /**
     * @var string|null
     *
     * @ORM\Column(name="student_aadhar", type="text", nullable=true)
     */
    private $studentAadhar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="aadhar_card", type="text", nullable=true)
     */
    private $aadharCard;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guardian_name", type="text", nullable=true)
     */
    private $guardianName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guardian_relation", type="text", nullable=true)
     */
    private $guardianRelation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guardian_aadhar", type="text", nullable=true)
     */
    private $guardianAadhar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="state_of_domicile", type="text", nullable=true)
     */
    private $stateOfDomicile;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postal_address", type="text", nullable=true)
     */
    private $postalAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="telephone_number", type="text", nullable=true)
     */
    private $telephoneNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email_address", type="text", nullable=true)
     */
    private $emailAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="preferred_branch", type="text", nullable=true)
     */
    private $preferredBranch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="class", type="text", nullable=true)
     */
    private $class;

    /**
     * @var string|null
     *
     * @ORM\Column(name="school_admission_required", type="text", nullable=true)
     */
    private $schoolAdmissionRequired;

    /**
     * @var string|null
     *
     * @ORM\Column(name="school_name", type="text", nullable=true)
     */
    private $schoolName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="board_name", type="text", nullable=true)
     */
    private $boardName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="medium", type="text", nullable=true)
     */
    private $medium;

    /**
     * @var string|null
     *
     * @ORM\Column(name="family_monthly_income", type="text", nullable=true)
     */
    private $familyMonthlyIncome;

    /**
     * @var string|null
     *
     * @ORM\Column(name="total_family_members", type="text", nullable=true)
     */
    private $totalFamilyMembers;

    /**
     * @var string|null
     *
     * @ORM\Column(name="payment_mode", type="text", nullable=true)
     */
    private $paymentMode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="c_authentication_code", type="text", nullable=true)
     */
    private $cAuthenticationCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="transaction_id", type="text", nullable=true)
     */
    private $transactionId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="student_id", type="text", nullable=true)
     */
    private $studentId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject_select", type="text", nullable=true)
     */
    private $subjectSelect;

    /**
     * @var string|null
     *
     * @ORM\Column(name="module", type="text", nullable=true)
     */
    private $module;

    /**
     * @var string|null
     *
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photo_url", type="text", nullable=true)
     */
    private $photoUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="id_card_issued", type="text", nullable=true)
     */
    private $idCardIssued;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="text", nullable=true)
     */
    private $status;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="effective_from", type="date", nullable=true)
     */
    private $effectiveFrom;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="scode", type="text", nullable=true)
     */
    private $scode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="updated_by", type="text", nullable=true)
     */
    private $updatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private $updatedOn;



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
     * Set typeOfAdmission.
     *
     * @param string|null $typeOfAdmission
     *
     * @return Student
     */
    public function setTypeOfAdmission($typeOfAdmission = null)
    {
        $this->typeOfAdmission = $typeOfAdmission;

        return $this;
    }

    /**
     * Get typeOfAdmission.
     *
     * @return string|null
     */
    public function getTypeOfAdmission()
    {
        return $this->typeOfAdmission;
    }

    /**
     * Set studentName.
     *
     * @param string|null $studentName
     *
     * @return Student
     */
    public function setStudentName($studentName = null)
    {
        $this->studentName = $studentName;

        return $this;
    }

    /**
     * Get studentName.
     *
     * @return string|null
     */
    public function getStudentName()
    {
        return $this->studentName;
    }

    /**
     * Set dateOfBirth.
     *
     * @param \DateTime|null $dateOfBirth
     *
     * @return Student
     */
    public function setDateOfBirth($dateOfBirth = null)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return \DateTime|null
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set gender.
     *
     * @param string|null $gender
     *
     * @return Student
     */
    public function setGender($gender = null)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string|null
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set studentPhoto.
     *
     * @param string|null $studentPhoto
     *
     * @return Student
     */
    public function setStudentPhoto($studentPhoto = null)
    {
        $this->studentPhoto = $studentPhoto;

        return $this;
    }

    /**
     * Get studentPhoto.
     *
     * @return string|null
     */
    public function getStudentPhoto()
    {
        return $this->studentPhoto;
    }

    /**
     * Set aadharAvailable.
     *
     * @param string|null $aadharAvailable
     *
     * @return Student
     */
    public function setAadharAvailable($aadharAvailable = null)
    {
        $this->aadharAvailable = $aadharAvailable;

        return $this;
    }

    /**
     * Get aadharAvailable.
     *
     * @return string|null
     */
    public function getAadharAvailable()
    {
        return $this->aadharAvailable;
    }

    /**
     * Set studentAadhar.
     *
     * @param string|null $studentAadhar
     *
     * @return Student
     */
    public function setStudentAadhar($studentAadhar = null)
    {
        $this->studentAadhar = $studentAadhar;

        return $this;
    }

    /**
     * Get studentAadhar.
     *
     * @return string|null
     */
    public function getStudentAadhar()
    {
        return $this->studentAadhar;
    }

    /**
     * Set aadharCard.
     *
     * @param string|null $aadharCard
     *
     * @return Student
     */
    public function setAadharCard($aadharCard = null)
    {
        $this->aadharCard = $aadharCard;

        return $this;
    }

    /**
     * Get aadharCard.
     *
     * @return string|null
     */
    public function getAadharCard()
    {
        return $this->aadharCard;
    }

    /**
     * Set guardianName.
     *
     * @param string|null $guardianName
     *
     * @return Student
     */
    public function setGuardianName($guardianName = null)
    {
        $this->guardianName = $guardianName;

        return $this;
    }

    /**
     * Get guardianName.
     *
     * @return string|null
     */
    public function getGuardianName()
    {
        return $this->guardianName;
    }

    /**
     * Set guardianRelation.
     *
     * @param string|null $guardianRelation
     *
     * @return Student
     */
    public function setGuardianRelation($guardianRelation = null)
    {
        $this->guardianRelation = $guardianRelation;

        return $this;
    }

    /**
     * Get guardianRelation.
     *
     * @return string|null
     */
    public function getGuardianRelation()
    {
        return $this->guardianRelation;
    }

    /**
     * Set guardianAadhar.
     *
     * @param string|null $guardianAadhar
     *
     * @return Student
     */
    public function setGuardianAadhar($guardianAadhar = null)
    {
        $this->guardianAadhar = $guardianAadhar;

        return $this;
    }

    /**
     * Get guardianAadhar.
     *
     * @return string|null
     */
    public function getGuardianAadhar()
    {
        return $this->guardianAadhar;
    }

    /**
     * Set stateOfDomicile.
     *
     * @param string|null $stateOfDomicile
     *
     * @return Student
     */
    public function setStateOfDomicile($stateOfDomicile = null)
    {
        $this->stateOfDomicile = $stateOfDomicile;

        return $this;
    }

    /**
     * Get stateOfDomicile.
     *
     * @return string|null
     */
    public function getStateOfDomicile()
    {
        return $this->stateOfDomicile;
    }

    /**
     * Set postalAddress.
     *
     * @param string|null $postalAddress
     *
     * @return Student
     */
    public function setPostalAddress($postalAddress = null)
    {
        $this->postalAddress = $postalAddress;

        return $this;
    }

    /**
     * Get postalAddress.
     *
     * @return string|null
     */
    public function getPostalAddress()
    {
        return $this->postalAddress;
    }

    /**
     * Set telephoneNumber.
     *
     * @param string|null $telephoneNumber
     *
     * @return Student
     */
    public function setTelephoneNumber($telephoneNumber = null)
    {
        $this->telephoneNumber = $telephoneNumber;

        return $this;
    }

    /**
     * Get telephoneNumber.
     *
     * @return string|null
     */
    public function getTelephoneNumber()
    {
        return $this->telephoneNumber;
    }

    /**
     * Set emailAddress.
     *
     * @param string|null $emailAddress
     *
     * @return Student
     */
    public function setEmailAddress($emailAddress = null)
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get emailAddress.
     *
     * @return string|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Set preferredBranch.
     *
     * @param string|null $preferredBranch
     *
     * @return Student
     */
    public function setPreferredBranch($preferredBranch = null)
    {
        $this->preferredBranch = $preferredBranch;

        return $this;
    }

    /**
     * Get preferredBranch.
     *
     * @return string|null
     */
    public function getPreferredBranch()
    {
        return $this->preferredBranch;
    }

    /**
     * Set class.
     *
     * @param string|null $class
     *
     * @return Student
     */
    public function setClass($class = null)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class.
     *
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set schoolAdmissionRequired.
     *
     * @param string|null $schoolAdmissionRequired
     *
     * @return Student
     */
    public function setSchoolAdmissionRequired($schoolAdmissionRequired = null)
    {
        $this->schoolAdmissionRequired = $schoolAdmissionRequired;

        return $this;
    }

    /**
     * Get schoolAdmissionRequired.
     *
     * @return string|null
     */
    public function getSchoolAdmissionRequired()
    {
        return $this->schoolAdmissionRequired;
    }

    /**
     * Set schoolName.
     *
     * @param string|null $schoolName
     *
     * @return Student
     */
    public function setSchoolName($schoolName = null)
    {
        $this->schoolName = $schoolName;

        return $this;
    }

    /**
     * Get schoolName.
     *
     * @return string|null
     */
    public function getSchoolName()
    {
        return $this->schoolName;
    }

    /**
     * Set boardName.
     *
     * @param string|null $boardName
     *
     * @return Student
     */
    public function setBoardName($boardName = null)
    {
        $this->boardName = $boardName;

        return $this;
    }

    /**
     * Get boardName.
     *
     * @return string|null
     */
    public function getBoardName()
    {
        return $this->boardName;
    }

    /**
     * Set medium.
     *
     * @param string|null $medium
     *
     * @return Student
     */
    public function setMedium($medium = null)
    {
        $this->medium = $medium;

        return $this;
    }

    /**
     * Get medium.
     *
     * @return string|null
     */
    public function getMedium()
    {
        return $this->medium;
    }

    /**
     * Set familyMonthlyIncome.
     *
     * @param string|null $familyMonthlyIncome
     *
     * @return Student
     */
    public function setFamilyMonthlyIncome($familyMonthlyIncome = null)
    {
        $this->familyMonthlyIncome = $familyMonthlyIncome;

        return $this;
    }

    /**
     * Get familyMonthlyIncome.
     *
     * @return string|null
     */
    public function getFamilyMonthlyIncome()
    {
        return $this->familyMonthlyIncome;
    }

    /**
     * Set totalFamilyMembers.
     *
     * @param string|null $totalFamilyMembers
     *
     * @return Student
     */
    public function setTotalFamilyMembers($totalFamilyMembers = null)
    {
        $this->totalFamilyMembers = $totalFamilyMembers;

        return $this;
    }

    /**
     * Get totalFamilyMembers.
     *
     * @return string|null
     */
    public function getTotalFamilyMembers()
    {
        return $this->totalFamilyMembers;
    }

    /**
     * Set paymentMode.
     *
     * @param string|null $paymentMode
     *
     * @return Student
     */
    public function setPaymentMode($paymentMode = null)
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    /**
     * Get paymentMode.
     *
     * @return string|null
     */
    public function getPaymentMode()
    {
        return $this->paymentMode;
    }

    /**
     * Set cAuthenticationCode.
     *
     * @param string|null $cAuthenticationCode
     *
     * @return Student
     */
    public function setCAuthenticationCode($cAuthenticationCode = null)
    {
        $this->cAuthenticationCode = $cAuthenticationCode;

        return $this;
    }

    /**
     * Get cAuthenticationCode.
     *
     * @return string|null
     */
    public function getCAuthenticationCode()
    {
        return $this->cAuthenticationCode;
    }

    /**
     * Set transactionId.
     *
     * @param string|null $transactionId
     *
     * @return Student
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
     * Set studentId.
     *
     * @param string|null $studentId
     *
     * @return Student
     */
    public function setStudentId($studentId = null)
    {
        $this->studentId = $studentId;

        return $this;
    }

    /**
     * Get studentId.
     *
     * @return string|null
     */
    public function getStudentId()
    {
        return $this->studentId;
    }

    /**
     * Set subjectSelect.
     *
     * @param string|null $subjectSelect
     *
     * @return Student
     */
    public function setSubjectSelect($subjectSelect = null)
    {
        $this->subjectSelect = $subjectSelect;

        return $this;
    }

    /**
     * Get subjectSelect.
     *
     * @return string|null
     */
    public function getSubjectSelect()
    {
        return $this->subjectSelect;
    }

    /**
     * Set module.
     *
     * @param string|null $module
     *
     * @return Student
     */
    public function setModule($module = null)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module.
     *
     * @return string|null
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set category.
     *
     * @param string|null $category
     *
     * @return Student
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
     * Set photoUrl.
     *
     * @param string|null $photoUrl
     *
     * @return Student
     */
    public function setPhotoUrl($photoUrl = null)
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    /**
     * Get photoUrl.
     *
     * @return string|null
     */
    public function getPhotoUrl()
    {
        return $this->photoUrl;
    }

    /**
     * Set idCardIssued.
     *
     * @param string|null $idCardIssued
     *
     * @return Student
     */
    public function setIdCardIssued($idCardIssued = null)
    {
        $this->idCardIssued = $idCardIssued;

        return $this;
    }

    /**
     * Get idCardIssued.
     *
     * @return string|null
     */
    public function getIdCardIssued()
    {
        return $this->idCardIssued;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return Student
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
     * Set effectiveFrom.
     *
     * @param \DateTime|null $effectiveFrom
     *
     * @return Student
     */
    public function setEffectiveFrom($effectiveFrom = null)
    {
        $this->effectiveFrom = $effectiveFrom;

        return $this;
    }

    /**
     * Get effectiveFrom.
     *
     * @return \DateTime|null
     */
    public function getEffectiveFrom()
    {
        return $this->effectiveFrom;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return Student
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
     * Set scode.
     *
     * @param string|null $scode
     *
     * @return Student
     */
    public function setScode($scode = null)
    {
        $this->scode = $scode;

        return $this;
    }

    /**
     * Get scode.
     *
     * @return string|null
     */
    public function getScode()
    {
        return $this->scode;
    }

    /**
     * Set updatedBy.
     *
     * @param string|null $updatedBy
     *
     * @return Student
     */
    public function setUpdatedBy($updatedBy = null)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy.
     *
     * @return string|null
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTime|null $updatedOn
     *
     * @return Student
     */
    public function setUpdatedOn($updatedOn = null)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return \DateTime|null
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }
}
