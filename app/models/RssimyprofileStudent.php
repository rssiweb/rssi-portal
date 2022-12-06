<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * RssimyprofileStudent
 *
 * @ORM\Table(name="rssimyprofile_student")
 * @ORM\Entity
 */
class RssimyprofileStudent
{
    /**
     * @var string
     *
     * @ORM\Column(name="student_id", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="rssimyprofile_student_student_id_seq", allocationSize=1, initialValue=1)
     */
    private $studentId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="roll_number", type="text", nullable=true)
     */
    private $rollNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="studentname", type="text", nullable=true)
     */
    private $studentname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gender", type="text", nullable=true)
     */
    private $gender;

    /**
     * @var string|null
     *
     * @ORM\Column(name="age", type="text", nullable=true)
     */
    private $age;

    /**
     * @var string|null
     *
     * @ORM\Column(name="class", type="text", nullable=true)
     */
    private $class;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contact", type="text", nullable=true)
     */
    private $contact;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guardiansname", type="text", nullable=true)
     */
    private $guardiansname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="relationwithstudent", type="text", nullable=true)
     */
    private $relationwithstudent;

    /**
     * @var string|null
     *
     * @ORM\Column(name="studentaadhar", type="text", nullable=true)
     */
    private $studentaadhar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="guardianaadhar", type="text", nullable=true)
     */
    private $guardianaadhar;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dateofbirth", type="text", nullable=true)
     */
    private $dateofbirth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postaladdress", type="text", nullable=true)
     */
    private $postaladdress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nameofthesubjects", type="text", nullable=true)
     */
    private $nameofthesubjects;

    /**
     * @var string|null
     *
     * @ORM\Column(name="preferredbranch", type="text", nullable=true)
     */
    private $preferredbranch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nameoftheschool", type="text", nullable=true)
     */
    private $nameoftheschool;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nameoftheboard", type="text", nullable=true)
     */
    private $nameoftheboard;

    /**
     * @var string|null
     *
     * @ORM\Column(name="stateofdomicile", type="text", nullable=true)
     */
    private $stateofdomicile;

    /**
     * @var string|null
     *
     * @ORM\Column(name="emailaddress", type="text", nullable=true)
     */
    private $emailaddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="schooladmissionrequired", type="text", nullable=true)
     */
    private $schooladmissionrequired;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="text", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nameofvendorfoundation", type="text", nullable=true)
     */
    private $nameofvendorfoundation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photourl", type="text", nullable=true)
     */
    private $photourl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="familymonthlyincome", type="text", nullable=true)
     */
    private $familymonthlyincome;

    /**
     * @var string|null
     *
     * @ORM\Column(name="totalnumberoffamilymembers", type="text", nullable=true)
     */
    private $totalnumberoffamilymembers;

    /**
     * @var string|null
     *
     * @ORM\Column(name="medium", type="text", nullable=true)
     */
    private $medium;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mydocument", type="text", nullable=true)
     */
    private $mydocument;

    /**
     * @var string|null
     *
     * @ORM\Column(name="extracolumn", type="text", nullable=true)
     */
    private $extracolumn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="colors", type="text", nullable=true)
     */
    private $colors;

    /**
     * @var string|null
     *
     * @ORM\Column(name="classurl", type="text", nullable=true)
     */
    private $classurl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="badge", type="text", nullable=true)
     */
    private $badge;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filterstatus", type="text", nullable=true)
     */
    private $filterstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="allocationdate", type="text", nullable=true)
     */
    private $allocationdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="maxclass", type="text", nullable=true)
     */
    private $maxclass;

    /**
     * @var string|null
     *
     * @ORM\Column(name="attd", type="text", nullable=true)
     */
    private $attd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cltaken", type="text", nullable=true)
     */
    private $cltaken;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sltaken", type="text", nullable=true)
     */
    private $sltaken;

    /**
     * @var string|null
     *
     * @ORM\Column(name="othtaken", type="text", nullable=true)
     */
    private $othtaken;

    /**
     * @var string|null
     *
     * @ORM\Column(name="doa", type="text", nullable=true)
     */
    private $doa;

    /**
     * @var string|null
     *
     * @ORM\Column(name="feesflag", type="text", nullable=true)
     */
    private $feesflag;

    /**
     * @var string|null
     *
     * @ORM\Column(name="module", type="text", nullable=true)
     */
    private $module;

    /**
     * @var string|null
     *
     * @ORM\Column(name="scode", type="text", nullable=true)
     */
    private $scode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="exitinterview", type="text", nullable=true)
     */
    private $exitinterview;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sipf", type="text", nullable=true)
     */
    private $sipf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="text", nullable=true)
     */
    private $password;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password_updated_by", type="text", nullable=true)
     */
    private $passwordUpdatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="password_updated_on", type="datetime", nullable=true)
     */
    private $passwordUpdatedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="upload_aadhar_card", type="text", nullable=true)
     */
    private $uploadAadharCard;

    /**
     * @var string|null
     *
     * @ORM\Column(name="special_service", type="text", nullable=true)
     */
    private $specialService;

    /**
     * @var string|null
     *
     * @ORM\Column(name="feecycle", type="text", nullable=true)
     */
    private $feecycle;

    /**
     * @var string|null
     *
     * @ORM\Column(name="default_pass_updated_by", type="text", nullable=true)
     */
    private $defaultPassUpdatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="default_pass_updated_on", type="datetime", nullable=true)
     */
    private $defaultPassUpdatedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="effectivefrom", type="text", nullable=true)
     */
    private $effectivefrom;



    /**
     * Get studentId.
     *
     * @return string
     */
    public function getStudentId()
    {
        return $this->studentId;
    }

    /**
     * Set category.
     *
     * @param string|null $category
     *
     * @return RssimyprofileStudent
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
     * Set rollNumber.
     *
     * @param string|null $rollNumber
     *
     * @return RssimyprofileStudent
     */
    public function setRollNumber($rollNumber = null)
    {
        $this->rollNumber = $rollNumber;

        return $this;
    }

    /**
     * Get rollNumber.
     *
     * @return string|null
     */
    public function getRollNumber()
    {
        return $this->rollNumber;
    }

    /**
     * Set studentname.
     *
     * @param string|null $studentname
     *
     * @return RssimyprofileStudent
     */
    public function setStudentname($studentname = null)
    {
        $this->studentname = $studentname;

        return $this;
    }

    /**
     * Get studentname.
     *
     * @return string|null
     */
    public function getStudentname()
    {
        return $this->studentname;
    }

    /**
     * Set gender.
     *
     * @param string|null $gender
     *
     * @return RssimyprofileStudent
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
     * Set age.
     *
     * @param string|null $age
     *
     * @return RssimyprofileStudent
     */
    public function setAge($age = null)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Get age.
     *
     * @return string|null
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Set class.
     *
     * @param string|null $class
     *
     * @return RssimyprofileStudent
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
     * Set contact.
     *
     * @param string|null $contact
     *
     * @return RssimyprofileStudent
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
     * Set guardiansname.
     *
     * @param string|null $guardiansname
     *
     * @return RssimyprofileStudent
     */
    public function setGuardiansname($guardiansname = null)
    {
        $this->guardiansname = $guardiansname;

        return $this;
    }

    /**
     * Get guardiansname.
     *
     * @return string|null
     */
    public function getGuardiansname()
    {
        return $this->guardiansname;
    }

    /**
     * Set relationwithstudent.
     *
     * @param string|null $relationwithstudent
     *
     * @return RssimyprofileStudent
     */
    public function setRelationwithstudent($relationwithstudent = null)
    {
        $this->relationwithstudent = $relationwithstudent;

        return $this;
    }

    /**
     * Get relationwithstudent.
     *
     * @return string|null
     */
    public function getRelationwithstudent()
    {
        return $this->relationwithstudent;
    }

    /**
     * Set studentaadhar.
     *
     * @param string|null $studentaadhar
     *
     * @return RssimyprofileStudent
     */
    public function setStudentaadhar($studentaadhar = null)
    {
        $this->studentaadhar = $studentaadhar;

        return $this;
    }

    /**
     * Get studentaadhar.
     *
     * @return string|null
     */
    public function getStudentaadhar()
    {
        return $this->studentaadhar;
    }

    /**
     * Set guardianaadhar.
     *
     * @param string|null $guardianaadhar
     *
     * @return RssimyprofileStudent
     */
    public function setGuardianaadhar($guardianaadhar = null)
    {
        $this->guardianaadhar = $guardianaadhar;

        return $this;
    }

    /**
     * Get guardianaadhar.
     *
     * @return string|null
     */
    public function getGuardianaadhar()
    {
        return $this->guardianaadhar;
    }

    /**
     * Set dateofbirth.
     *
     * @param string|null $dateofbirth
     *
     * @return RssimyprofileStudent
     */
    public function setDateofbirth($dateofbirth = null)
    {
        $this->dateofbirth = $dateofbirth;

        return $this;
    }

    /**
     * Get dateofbirth.
     *
     * @return string|null
     */
    public function getDateofbirth()
    {
        return $this->dateofbirth;
    }

    /**
     * Set postaladdress.
     *
     * @param string|null $postaladdress
     *
     * @return RssimyprofileStudent
     */
    public function setPostaladdress($postaladdress = null)
    {
        $this->postaladdress = $postaladdress;

        return $this;
    }

    /**
     * Get postaladdress.
     *
     * @return string|null
     */
    public function getPostaladdress()
    {
        return $this->postaladdress;
    }

    /**
     * Set nameofthesubjects.
     *
     * @param string|null $nameofthesubjects
     *
     * @return RssimyprofileStudent
     */
    public function setNameofthesubjects($nameofthesubjects = null)
    {
        $this->nameofthesubjects = $nameofthesubjects;

        return $this;
    }

    /**
     * Get nameofthesubjects.
     *
     * @return string|null
     */
    public function getNameofthesubjects()
    {
        return $this->nameofthesubjects;
    }

    /**
     * Set preferredbranch.
     *
     * @param string|null $preferredbranch
     *
     * @return RssimyprofileStudent
     */
    public function setPreferredbranch($preferredbranch = null)
    {
        $this->preferredbranch = $preferredbranch;

        return $this;
    }

    /**
     * Get preferredbranch.
     *
     * @return string|null
     */
    public function getPreferredbranch()
    {
        return $this->preferredbranch;
    }

    /**
     * Set nameoftheschool.
     *
     * @param string|null $nameoftheschool
     *
     * @return RssimyprofileStudent
     */
    public function setNameoftheschool($nameoftheschool = null)
    {
        $this->nameoftheschool = $nameoftheschool;

        return $this;
    }

    /**
     * Get nameoftheschool.
     *
     * @return string|null
     */
    public function getNameoftheschool()
    {
        return $this->nameoftheschool;
    }

    /**
     * Set nameoftheboard.
     *
     * @param string|null $nameoftheboard
     *
     * @return RssimyprofileStudent
     */
    public function setNameoftheboard($nameoftheboard = null)
    {
        $this->nameoftheboard = $nameoftheboard;

        return $this;
    }

    /**
     * Get nameoftheboard.
     *
     * @return string|null
     */
    public function getNameoftheboard()
    {
        return $this->nameoftheboard;
    }

    /**
     * Set stateofdomicile.
     *
     * @param string|null $stateofdomicile
     *
     * @return RssimyprofileStudent
     */
    public function setStateofdomicile($stateofdomicile = null)
    {
        $this->stateofdomicile = $stateofdomicile;

        return $this;
    }

    /**
     * Get stateofdomicile.
     *
     * @return string|null
     */
    public function getStateofdomicile()
    {
        return $this->stateofdomicile;
    }

    /**
     * Set emailaddress.
     *
     * @param string|null $emailaddress
     *
     * @return RssimyprofileStudent
     */
    public function setEmailaddress($emailaddress = null)
    {
        $this->emailaddress = $emailaddress;

        return $this;
    }

    /**
     * Get emailaddress.
     *
     * @return string|null
     */
    public function getEmailaddress()
    {
        return $this->emailaddress;
    }

    /**
     * Set schooladmissionrequired.
     *
     * @param string|null $schooladmissionrequired
     *
     * @return RssimyprofileStudent
     */
    public function setSchooladmissionrequired($schooladmissionrequired = null)
    {
        $this->schooladmissionrequired = $schooladmissionrequired;

        return $this;
    }

    /**
     * Get schooladmissionrequired.
     *
     * @return string|null
     */
    public function getSchooladmissionrequired()
    {
        return $this->schooladmissionrequired;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return RssimyprofileStudent
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
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return RssimyprofileStudent
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
     * Set nameofvendorfoundation.
     *
     * @param string|null $nameofvendorfoundation
     *
     * @return RssimyprofileStudent
     */
    public function setNameofvendorfoundation($nameofvendorfoundation = null)
    {
        $this->nameofvendorfoundation = $nameofvendorfoundation;

        return $this;
    }

    /**
     * Get nameofvendorfoundation.
     *
     * @return string|null
     */
    public function getNameofvendorfoundation()
    {
        return $this->nameofvendorfoundation;
    }

    /**
     * Set photourl.
     *
     * @param string|null $photourl
     *
     * @return RssimyprofileStudent
     */
    public function setPhotourl($photourl = null)
    {
        $this->photourl = $photourl;

        return $this;
    }

    /**
     * Get photourl.
     *
     * @return string|null
     */
    public function getPhotourl()
    {
        return $this->photourl;
    }

    /**
     * Set familymonthlyincome.
     *
     * @param string|null $familymonthlyincome
     *
     * @return RssimyprofileStudent
     */
    public function setFamilymonthlyincome($familymonthlyincome = null)
    {
        $this->familymonthlyincome = $familymonthlyincome;

        return $this;
    }

    /**
     * Get familymonthlyincome.
     *
     * @return string|null
     */
    public function getFamilymonthlyincome()
    {
        return $this->familymonthlyincome;
    }

    /**
     * Set totalnumberoffamilymembers.
     *
     * @param string|null $totalnumberoffamilymembers
     *
     * @return RssimyprofileStudent
     */
    public function setTotalnumberoffamilymembers($totalnumberoffamilymembers = null)
    {
        $this->totalnumberoffamilymembers = $totalnumberoffamilymembers;

        return $this;
    }

    /**
     * Get totalnumberoffamilymembers.
     *
     * @return string|null
     */
    public function getTotalnumberoffamilymembers()
    {
        return $this->totalnumberoffamilymembers;
    }

    /**
     * Set medium.
     *
     * @param string|null $medium
     *
     * @return RssimyprofileStudent
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
     * Set mydocument.
     *
     * @param string|null $mydocument
     *
     * @return RssimyprofileStudent
     */
    public function setMydocument($mydocument = null)
    {
        $this->mydocument = $mydocument;

        return $this;
    }

    /**
     * Get mydocument.
     *
     * @return string|null
     */
    public function getMydocument()
    {
        return $this->mydocument;
    }

    /**
     * Set extracolumn.
     *
     * @param string|null $extracolumn
     *
     * @return RssimyprofileStudent
     */
    public function setExtracolumn($extracolumn = null)
    {
        $this->extracolumn = $extracolumn;

        return $this;
    }

    /**
     * Get extracolumn.
     *
     * @return string|null
     */
    public function getExtracolumn()
    {
        return $this->extracolumn;
    }

    /**
     * Set colors.
     *
     * @param string|null $colors
     *
     * @return RssimyprofileStudent
     */
    public function setColors($colors = null)
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Get colors.
     *
     * @return string|null
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * Set classurl.
     *
     * @param string|null $classurl
     *
     * @return RssimyprofileStudent
     */
    public function setClassurl($classurl = null)
    {
        $this->classurl = $classurl;

        return $this;
    }

    /**
     * Get classurl.
     *
     * @return string|null
     */
    public function getClassurl()
    {
        return $this->classurl;
    }

    /**
     * Set badge.
     *
     * @param string|null $badge
     *
     * @return RssimyprofileStudent
     */
    public function setBadge($badge = null)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Get badge.
     *
     * @return string|null
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * Set filterstatus.
     *
     * @param string|null $filterstatus
     *
     * @return RssimyprofileStudent
     */
    public function setFilterstatus($filterstatus = null)
    {
        $this->filterstatus = $filterstatus;

        return $this;
    }

    /**
     * Get filterstatus.
     *
     * @return string|null
     */
    public function getFilterstatus()
    {
        return $this->filterstatus;
    }

    /**
     * Set allocationdate.
     *
     * @param string|null $allocationdate
     *
     * @return RssimyprofileStudent
     */
    public function setAllocationdate($allocationdate = null)
    {
        $this->allocationdate = $allocationdate;

        return $this;
    }

    /**
     * Get allocationdate.
     *
     * @return string|null
     */
    public function getAllocationdate()
    {
        return $this->allocationdate;
    }

    /**
     * Set maxclass.
     *
     * @param string|null $maxclass
     *
     * @return RssimyprofileStudent
     */
    public function setMaxclass($maxclass = null)
    {
        $this->maxclass = $maxclass;

        return $this;
    }

    /**
     * Get maxclass.
     *
     * @return string|null
     */
    public function getMaxclass()
    {
        return $this->maxclass;
    }

    /**
     * Set attd.
     *
     * @param string|null $attd
     *
     * @return RssimyprofileStudent
     */
    public function setAttd($attd = null)
    {
        $this->attd = $attd;

        return $this;
    }

    /**
     * Get attd.
     *
     * @return string|null
     */
    public function getAttd()
    {
        return $this->attd;
    }

    /**
     * Set cltaken.
     *
     * @param string|null $cltaken
     *
     * @return RssimyprofileStudent
     */
    public function setCltaken($cltaken = null)
    {
        $this->cltaken = $cltaken;

        return $this;
    }

    /**
     * Get cltaken.
     *
     * @return string|null
     */
    public function getCltaken()
    {
        return $this->cltaken;
    }

    /**
     * Set sltaken.
     *
     * @param string|null $sltaken
     *
     * @return RssimyprofileStudent
     */
    public function setSltaken($sltaken = null)
    {
        $this->sltaken = $sltaken;

        return $this;
    }

    /**
     * Get sltaken.
     *
     * @return string|null
     */
    public function getSltaken()
    {
        return $this->sltaken;
    }

    /**
     * Set othtaken.
     *
     * @param string|null $othtaken
     *
     * @return RssimyprofileStudent
     */
    public function setOthtaken($othtaken = null)
    {
        $this->othtaken = $othtaken;

        return $this;
    }

    /**
     * Get othtaken.
     *
     * @return string|null
     */
    public function getOthtaken()
    {
        return $this->othtaken;
    }

    /**
     * Set doa.
     *
     * @param string|null $doa
     *
     * @return RssimyprofileStudent
     */
    public function setDoa($doa = null)
    {
        $this->doa = $doa;

        return $this;
    }

    /**
     * Get doa.
     *
     * @return string|null
     */
    public function getDoa()
    {
        return $this->doa;
    }

    /**
     * Set feesflag.
     *
     * @param string|null $feesflag
     *
     * @return RssimyprofileStudent
     */
    public function setFeesflag($feesflag = null)
    {
        $this->feesflag = $feesflag;

        return $this;
    }

    /**
     * Get feesflag.
     *
     * @return string|null
     */
    public function getFeesflag()
    {
        return $this->feesflag;
    }

    /**
     * Set module.
     *
     * @param string|null $module
     *
     * @return RssimyprofileStudent
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
     * Set scode.
     *
     * @param string|null $scode
     *
     * @return RssimyprofileStudent
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
     * Set exitinterview.
     *
     * @param string|null $exitinterview
     *
     * @return RssimyprofileStudent
     */
    public function setExitinterview($exitinterview = null)
    {
        $this->exitinterview = $exitinterview;

        return $this;
    }

    /**
     * Get exitinterview.
     *
     * @return string|null
     */
    public function getExitinterview()
    {
        return $this->exitinterview;
    }

    /**
     * Set sipf.
     *
     * @param string|null $sipf
     *
     * @return RssimyprofileStudent
     */
    public function setSipf($sipf = null)
    {
        $this->sipf = $sipf;

        return $this;
    }

    /**
     * Get sipf.
     *
     * @return string|null
     */
    public function getSipf()
    {
        return $this->sipf;
    }

    /**
     * Set password.
     *
     * @param string|null $password
     *
     * @return RssimyprofileStudent
     */
    public function setPassword($password = null)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set passwordUpdatedBy.
     *
     * @param string|null $passwordUpdatedBy
     *
     * @return RssimyprofileStudent
     */
    public function setPasswordUpdatedBy($passwordUpdatedBy = null)
    {
        $this->passwordUpdatedBy = $passwordUpdatedBy;

        return $this;
    }

    /**
     * Get passwordUpdatedBy.
     *
     * @return string|null
     */
    public function getPasswordUpdatedBy()
    {
        return $this->passwordUpdatedBy;
    }

    /**
     * Set passwordUpdatedOn.
     *
     * @param \DateTime|null $passwordUpdatedOn
     *
     * @return RssimyprofileStudent
     */
    public function setPasswordUpdatedOn($passwordUpdatedOn = null)
    {
        $this->passwordUpdatedOn = $passwordUpdatedOn;

        return $this;
    }

    /**
     * Get passwordUpdatedOn.
     *
     * @return \DateTime|null
     */
    public function getPasswordUpdatedOn()
    {
        return $this->passwordUpdatedOn;
    }

    /**
     * Set uploadAadharCard.
     *
     * @param string|null $uploadAadharCard
     *
     * @return RssimyprofileStudent
     */
    public function setUploadAadharCard($uploadAadharCard = null)
    {
        $this->uploadAadharCard = $uploadAadharCard;

        return $this;
    }

    /**
     * Get uploadAadharCard.
     *
     * @return string|null
     */
    public function getUploadAadharCard()
    {
        return $this->uploadAadharCard;
    }

    /**
     * Set specialService.
     *
     * @param string|null $specialService
     *
     * @return RssimyprofileStudent
     */
    public function setSpecialService($specialService = null)
    {
        $this->specialService = $specialService;

        return $this;
    }

    /**
     * Get specialService.
     *
     * @return string|null
     */
    public function getSpecialService()
    {
        return $this->specialService;
    }

    /**
     * Set feecycle.
     *
     * @param string|null $feecycle
     *
     * @return RssimyprofileStudent
     */
    public function setFeecycle($feecycle = null)
    {
        $this->feecycle = $feecycle;

        return $this;
    }

    /**
     * Get feecycle.
     *
     * @return string|null
     */
    public function getFeecycle()
    {
        return $this->feecycle;
    }

    /**
     * Set defaultPassUpdatedBy.
     *
     * @param string|null $defaultPassUpdatedBy
     *
     * @return RssimyprofileStudent
     */
    public function setDefaultPassUpdatedBy($defaultPassUpdatedBy = null)
    {
        $this->defaultPassUpdatedBy = $defaultPassUpdatedBy;

        return $this;
    }

    /**
     * Get defaultPassUpdatedBy.
     *
     * @return string|null
     */
    public function getDefaultPassUpdatedBy()
    {
        return $this->defaultPassUpdatedBy;
    }

    /**
     * Set defaultPassUpdatedOn.
     *
     * @param \DateTime|null $defaultPassUpdatedOn
     *
     * @return RssimyprofileStudent
     */
    public function setDefaultPassUpdatedOn($defaultPassUpdatedOn = null)
    {
        $this->defaultPassUpdatedOn = $defaultPassUpdatedOn;

        return $this;
    }

    /**
     * Get defaultPassUpdatedOn.
     *
     * @return \DateTime|null
     */
    public function getDefaultPassUpdatedOn()
    {
        return $this->defaultPassUpdatedOn;
    }

    /**
     * Set effectivefrom.
     *
     * @param string|null $effectivefrom
     *
     * @return RssimyprofileStudent
     */
    public function setEffectivefrom($effectivefrom = null)
    {
        $this->effectivefrom = $effectivefrom;

        return $this;
    }

    /**
     * Get effectivefrom.
     *
     * @return string|null
     */
    public function getEffectivefrom()
    {
        return $this->effectivefrom;
    }
}
