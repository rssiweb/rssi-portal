<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * RssimyaccountMembers
 *
 * @ORM\Table(name="rssimyaccount_members")
 * @ORM\Entity
 */
class RssimyaccountMembers
{
    /**
     * @var string
     *
     * @ORM\Column(name="associatenumber", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="rssimyaccount_members_associatenumber_seq", allocationSize=1, initialValue=1)
     */
    private $associatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="doj", type="text", nullable=true)
     */
    private $doj;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullname", type="text", nullable=true)
     */
    private $fullname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="basebranch", type="text", nullable=true)
     */
    private $basebranch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gender", type="text", nullable=true)
     */
    private $gender;

    /**
     * @var string|null
     *
     * @ORM\Column(name="dateofbirth", type="text", nullable=true)
     */
    private $dateofbirth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="howyouwouldliketobeaddressed", type="text", nullable=true)
     */
    private $howyouwouldliketobeaddressed;

    /**
     * @var string|null
     *
     * @ORM\Column(name="currentaddress", type="text", nullable=true)
     */
    private $currentaddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="permanentaddress", type="text", nullable=true)
     */
    private $permanentaddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="languagedetailsenglish", type="text", nullable=true)
     */
    private $languagedetailsenglish;

    /**
     * @var string|null
     *
     * @ORM\Column(name="languagedetailshindi", type="text", nullable=true)
     */
    private $languagedetailshindi;

    /**
     * @var string|null
     *
     * @ORM\Column(name="workexperience", type="text", nullable=true)
     */
    private $workexperience;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nationalidentifier", type="text", nullable=true)
     */
    private $nationalidentifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="yourthoughtabouttheworkyouareengagedwith", type="text", nullable=true)
     */
    private $yourthoughtabouttheworkyouareengagedwith;

    /**
     * @var string|null
     *
     * @ORM\Column(name="applicationnumber", type="text", nullable=true)
     */
    private $applicationnumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="position", type="text", nullable=true)
     */
    private $position;

    /**
     * @var string|null
     *
     * @ORM\Column(name="approvedby", type="text", nullable=true)
     */
    private $approvedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associationstatus", type="text", nullable=true)
     */
    private $associationstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="effectivedate", type="text", nullable=true)
     */
    private $effectivedate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone", type="text", nullable=true)
     */
    private $phone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="identifier", type="text", nullable=true)
     */
    private $identifier;

    /**
     * @var string|null
     *
     * @ORM\Column(name="astatus", type="text", nullable=true)
     */
    private $astatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="badge", type="text", nullable=true)
     */
    private $badge;

    /**
     * @var string|null
     *
     * @ORM\Column(name="colors", type="text", nullable=true)
     */
    private $colors;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gm", type="text", nullable=true)
     */
    private $gm;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lastupdatedon", type="text", nullable=true)
     */
    private $lastupdatedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photo", type="text", nullable=true)
     */
    private $photo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mydoc", type="text", nullable=true)
     */
    private $mydoc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="class", type="text", nullable=true)
     */
    private $class;

    /**
     * @var string|null
     *
     * @ORM\Column(name="notification", type="text", nullable=true)
     */
    private $notification;

    /**
     * @var string|null
     *
     * @ORM\Column(name="age", type="text", nullable=true)
     */
    private $age;

    /**
     * @var string|null
     *
     * @ORM\Column(name="depb", type="text", nullable=true)
     */
    private $depb;

    /**
     * @var string|null
     *
     * @ORM\Column(name="attd", type="text", nullable=true)
     */
    private $attd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filterstatus", type="text", nullable=true)
     */
    private $filterstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="today", type="text", nullable=true)
     */
    private $today;

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
     * @ORM\Column(name="classtaken", type="text", nullable=true)
     */
    private $classtaken;

    /**
     * @var string|null
     *
     * @ORM\Column(name="leave", type="text", nullable=true)
     */
    private $leave;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ctp", type="text", nullable=true)
     */
    private $ctp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    private $feedback;

    /**
     * @var string|null
     *
     * @ORM\Column(name="evaluationpath", type="text", nullable=true)
     */
    private $evaluationpath;

    /**
     * @var string|null
     *
     * @ORM\Column(name="leaveapply", type="text", nullable=true)
     */
    private $leaveapply;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cl", type="text", nullable=true)
     */
    private $cl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sl", type="text", nullable=true)
     */
    private $sl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="el", type="text", nullable=true)
     */
    private $el;

    /**
     * @var string|null
     *
     * @ORM\Column(name="engagement", type="text", nullable=true)
     */
    private $engagement;

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
     * @ORM\Column(name="eltaken", type="text", nullable=true)
     */
    private $eltaken;

    /**
     * @var string|null
     *
     * @ORM\Column(name="othtaken", type="text", nullable=true)
     */
    private $othtaken;

    /**
     * @var string|null
     *
     * @ORM\Column(name="clbal", type="text", nullable=true)
     */
    private $clbal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="slbal", type="text", nullable=true)
     */
    private $slbal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="elbal", type="text", nullable=true)
     */
    private $elbal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="officialdoc", type="text", nullable=true)
     */
    private $officialdoc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="profile", type="text", nullable=true)
     */
    private $profile;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="text", nullable=true)
     */
    private $filename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fname", type="text", nullable=true)
     */
    private $fname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="quicklink", type="text", nullable=true)
     */
    private $quicklink;

    /**
     * @var string|null
     *
     * @ORM\Column(name="yos", type="text", nullable=true)
     */
    private $yos;

    /**
     * @var string|null
     *
     * @ORM\Column(name="role", type="text", nullable=true)
     */
    private $role;

    /**
     * @var string|null
     *
     * @ORM\Column(name="originaldoj", type="text", nullable=true)
     */
    private $originaldoj;

    /**
     * @var string|null
     *
     * @ORM\Column(name="iddoc", type="text", nullable=true)
     */
    private $iddoc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="vaccination", type="text", nullable=true)
     */
    private $vaccination;

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
     * @ORM\Column(name="questionflag", type="text", nullable=true)
     */
    private $questionflag;

    /**
     * @var string|null
     *
     * @ORM\Column(name="googlechat", type="text", nullable=true)
     */
    private $googlechat;

    /**
     * @var string|null
     *
     * @ORM\Column(name="adjustedleave", type="text", nullable=true)
     */
    private $adjustedleave;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipfl", type="text", nullable=true)
     */
    private $ipfl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="eduq", type="text", nullable=true)
     */
    private $eduq;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mjorsub", type="text", nullable=true)
     */
    private $mjorsub;

    /**
     * @var string|null
     *
     * @ORM\Column(name="disc", type="text", nullable=true)
     */
    private $disc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hbday", type="text", nullable=true)
     */
    private $hbday;

    /**
     * @var string|null
     *
     * @ORM\Column(name="on_leave", type="text", nullable=true)
     */
    private $onLeave;

    /**
     * @var string|null
     *
     * @ORM\Column(name="attd_pending", type="text", nullable=true)
     */
    private $attdPending;

    /**
     * @var string|null
     *
     * @ORM\Column(name="approveddate", type="text", nullable=true)
     */
    private $approveddate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=225, nullable=true)
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
     * Get associatenumber.
     *
     * @return string
     */
    public function getAssociatenumber()
    {
        return $this->associatenumber;
    }

    /**
     * Set doj.
     *
     * @param string|null $doj
     *
     * @return RssimyaccountMembers
     */
    public function setDoj($doj = null)
    {
        $this->doj = $doj;

        return $this;
    }

    /**
     * Get doj.
     *
     * @return string|null
     */
    public function getDoj()
    {
        return $this->doj;
    }

    /**
     * Set fullname.
     *
     * @param string|null $fullname
     *
     * @return RssimyaccountMembers
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
     * Set email.
     *
     * @param string|null $email
     *
     * @return RssimyaccountMembers
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
     * Set basebranch.
     *
     * @param string|null $basebranch
     *
     * @return RssimyaccountMembers
     */
    public function setBasebranch($basebranch = null)
    {
        $this->basebranch = $basebranch;

        return $this;
    }

    /**
     * Get basebranch.
     *
     * @return string|null
     */
    public function getBasebranch()
    {
        return $this->basebranch;
    }

    /**
     * Set gender.
     *
     * @param string|null $gender
     *
     * @return RssimyaccountMembers
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
     * Set dateofbirth.
     *
     * @param string|null $dateofbirth
     *
     * @return RssimyaccountMembers
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
     * Set howyouwouldliketobeaddressed.
     *
     * @param string|null $howyouwouldliketobeaddressed
     *
     * @return RssimyaccountMembers
     */
    public function setHowyouwouldliketobeaddressed($howyouwouldliketobeaddressed = null)
    {
        $this->howyouwouldliketobeaddressed = $howyouwouldliketobeaddressed;

        return $this;
    }

    /**
     * Get howyouwouldliketobeaddressed.
     *
     * @return string|null
     */
    public function getHowyouwouldliketobeaddressed()
    {
        return $this->howyouwouldliketobeaddressed;
    }

    /**
     * Set currentaddress.
     *
     * @param string|null $currentaddress
     *
     * @return RssimyaccountMembers
     */
    public function setCurrentaddress($currentaddress = null)
    {
        $this->currentaddress = $currentaddress;

        return $this;
    }

    /**
     * Get currentaddress.
     *
     * @return string|null
     */
    public function getCurrentaddress()
    {
        return $this->currentaddress;
    }

    /**
     * Set permanentaddress.
     *
     * @param string|null $permanentaddress
     *
     * @return RssimyaccountMembers
     */
    public function setPermanentaddress($permanentaddress = null)
    {
        $this->permanentaddress = $permanentaddress;

        return $this;
    }

    /**
     * Get permanentaddress.
     *
     * @return string|null
     */
    public function getPermanentaddress()
    {
        return $this->permanentaddress;
    }

    /**
     * Set languagedetailsenglish.
     *
     * @param string|null $languagedetailsenglish
     *
     * @return RssimyaccountMembers
     */
    public function setLanguagedetailsenglish($languagedetailsenglish = null)
    {
        $this->languagedetailsenglish = $languagedetailsenglish;

        return $this;
    }

    /**
     * Get languagedetailsenglish.
     *
     * @return string|null
     */
    public function getLanguagedetailsenglish()
    {
        return $this->languagedetailsenglish;
    }

    /**
     * Set languagedetailshindi.
     *
     * @param string|null $languagedetailshindi
     *
     * @return RssimyaccountMembers
     */
    public function setLanguagedetailshindi($languagedetailshindi = null)
    {
        $this->languagedetailshindi = $languagedetailshindi;

        return $this;
    }

    /**
     * Get languagedetailshindi.
     *
     * @return string|null
     */
    public function getLanguagedetailshindi()
    {
        return $this->languagedetailshindi;
    }

    /**
     * Set workexperience.
     *
     * @param string|null $workexperience
     *
     * @return RssimyaccountMembers
     */
    public function setWorkexperience($workexperience = null)
    {
        $this->workexperience = $workexperience;

        return $this;
    }

    /**
     * Get workexperience.
     *
     * @return string|null
     */
    public function getWorkexperience()
    {
        return $this->workexperience;
    }

    /**
     * Set nationalidentifier.
     *
     * @param string|null $nationalidentifier
     *
     * @return RssimyaccountMembers
     */
    public function setNationalidentifier($nationalidentifier = null)
    {
        $this->nationalidentifier = $nationalidentifier;

        return $this;
    }

    /**
     * Get nationalidentifier.
     *
     * @return string|null
     */
    public function getNationalidentifier()
    {
        return $this->nationalidentifier;
    }

    /**
     * Set yourthoughtabouttheworkyouareengagedwith.
     *
     * @param string|null $yourthoughtabouttheworkyouareengagedwith
     *
     * @return RssimyaccountMembers
     */
    public function setYourthoughtabouttheworkyouareengagedwith($yourthoughtabouttheworkyouareengagedwith = null)
    {
        $this->yourthoughtabouttheworkyouareengagedwith = $yourthoughtabouttheworkyouareengagedwith;

        return $this;
    }

    /**
     * Get yourthoughtabouttheworkyouareengagedwith.
     *
     * @return string|null
     */
    public function getYourthoughtabouttheworkyouareengagedwith()
    {
        return $this->yourthoughtabouttheworkyouareengagedwith;
    }

    /**
     * Set applicationnumber.
     *
     * @param string|null $applicationnumber
     *
     * @return RssimyaccountMembers
     */
    public function setApplicationnumber($applicationnumber = null)
    {
        $this->applicationnumber = $applicationnumber;

        return $this;
    }

    /**
     * Get applicationnumber.
     *
     * @return string|null
     */
    public function getApplicationnumber()
    {
        return $this->applicationnumber;
    }

    /**
     * Set position.
     *
     * @param string|null $position
     *
     * @return RssimyaccountMembers
     */
    public function setPosition($position = null)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string|null
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set approvedby.
     *
     * @param string|null $approvedby
     *
     * @return RssimyaccountMembers
     */
    public function setApprovedby($approvedby = null)
    {
        $this->approvedby = $approvedby;

        return $this;
    }

    /**
     * Get approvedby.
     *
     * @return string|null
     */
    public function getApprovedby()
    {
        return $this->approvedby;
    }

    /**
     * Set associationstatus.
     *
     * @param string|null $associationstatus
     *
     * @return RssimyaccountMembers
     */
    public function setAssociationstatus($associationstatus = null)
    {
        $this->associationstatus = $associationstatus;

        return $this;
    }

    /**
     * Get associationstatus.
     *
     * @return string|null
     */
    public function getAssociationstatus()
    {
        return $this->associationstatus;
    }

    /**
     * Set effectivedate.
     *
     * @param string|null $effectivedate
     *
     * @return RssimyaccountMembers
     */
    public function setEffectivedate($effectivedate = null)
    {
        $this->effectivedate = $effectivedate;

        return $this;
    }

    /**
     * Get effectivedate.
     *
     * @return string|null
     */
    public function getEffectivedate()
    {
        return $this->effectivedate;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return RssimyaccountMembers
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
     * Set phone.
     *
     * @param string|null $phone
     *
     * @return RssimyaccountMembers
     */
    public function setPhone($phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set identifier.
     *
     * @param string|null $identifier
     *
     * @return RssimyaccountMembers
     */
    public function setIdentifier($identifier = null)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier.
     *
     * @return string|null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set astatus.
     *
     * @param string|null $astatus
     *
     * @return RssimyaccountMembers
     */
    public function setAstatus($astatus = null)
    {
        $this->astatus = $astatus;

        return $this;
    }

    /**
     * Get astatus.
     *
     * @return string|null
     */
    public function getAstatus()
    {
        return $this->astatus;
    }

    /**
     * Set badge.
     *
     * @param string|null $badge
     *
     * @return RssimyaccountMembers
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
     * Set colors.
     *
     * @param string|null $colors
     *
     * @return RssimyaccountMembers
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
     * Set gm.
     *
     * @param string|null $gm
     *
     * @return RssimyaccountMembers
     */
    public function setGm($gm = null)
    {
        $this->gm = $gm;

        return $this;
    }

    /**
     * Get gm.
     *
     * @return string|null
     */
    public function getGm()
    {
        return $this->gm;
    }

    /**
     * Set lastupdatedon.
     *
     * @param string|null $lastupdatedon
     *
     * @return RssimyaccountMembers
     */
    public function setLastupdatedon($lastupdatedon = null)
    {
        $this->lastupdatedon = $lastupdatedon;

        return $this;
    }

    /**
     * Get lastupdatedon.
     *
     * @return string|null
     */
    public function getLastupdatedon()
    {
        return $this->lastupdatedon;
    }

    /**
     * Set photo.
     *
     * @param string|null $photo
     *
     * @return RssimyaccountMembers
     */
    public function setPhoto($photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo.
     *
     * @return string|null
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set mydoc.
     *
     * @param string|null $mydoc
     *
     * @return RssimyaccountMembers
     */
    public function setMydoc($mydoc = null)
    {
        $this->mydoc = $mydoc;

        return $this;
    }

    /**
     * Get mydoc.
     *
     * @return string|null
     */
    public function getMydoc()
    {
        return $this->mydoc;
    }

    /**
     * Set class.
     *
     * @param string|null $class
     *
     * @return RssimyaccountMembers
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
     * Set notification.
     *
     * @param string|null $notification
     *
     * @return RssimyaccountMembers
     */
    public function setNotification($notification = null)
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * Get notification.
     *
     * @return string|null
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * Set age.
     *
     * @param string|null $age
     *
     * @return RssimyaccountMembers
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
     * Set depb.
     *
     * @param string|null $depb
     *
     * @return RssimyaccountMembers
     */
    public function setDepb($depb = null)
    {
        $this->depb = $depb;

        return $this;
    }

    /**
     * Get depb.
     *
     * @return string|null
     */
    public function getDepb()
    {
        return $this->depb;
    }

    /**
     * Set attd.
     *
     * @param string|null $attd
     *
     * @return RssimyaccountMembers
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
     * Set filterstatus.
     *
     * @param string|null $filterstatus
     *
     * @return RssimyaccountMembers
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
     * Set today.
     *
     * @param string|null $today
     *
     * @return RssimyaccountMembers
     */
    public function setToday($today = null)
    {
        $this->today = $today;

        return $this;
    }

    /**
     * Get today.
     *
     * @return string|null
     */
    public function getToday()
    {
        return $this->today;
    }

    /**
     * Set allocationdate.
     *
     * @param string|null $allocationdate
     *
     * @return RssimyaccountMembers
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
     * @return RssimyaccountMembers
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
     * Set classtaken.
     *
     * @param string|null $classtaken
     *
     * @return RssimyaccountMembers
     */
    public function setClasstaken($classtaken = null)
    {
        $this->classtaken = $classtaken;

        return $this;
    }

    /**
     * Get classtaken.
     *
     * @return string|null
     */
    public function getClasstaken()
    {
        return $this->classtaken;
    }

    /**
     * Set leave.
     *
     * @param string|null $leave
     *
     * @return RssimyaccountMembers
     */
    public function setLeave($leave = null)
    {
        $this->leave = $leave;

        return $this;
    }

    /**
     * Get leave.
     *
     * @return string|null
     */
    public function getLeave()
    {
        return $this->leave;
    }

    /**
     * Set ctp.
     *
     * @param string|null $ctp
     *
     * @return RssimyaccountMembers
     */
    public function setCtp($ctp = null)
    {
        $this->ctp = $ctp;

        return $this;
    }

    /**
     * Get ctp.
     *
     * @return string|null
     */
    public function getCtp()
    {
        return $this->ctp;
    }

    /**
     * Set feedback.
     *
     * @param string|null $feedback
     *
     * @return RssimyaccountMembers
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
     * Set evaluationpath.
     *
     * @param string|null $evaluationpath
     *
     * @return RssimyaccountMembers
     */
    public function setEvaluationpath($evaluationpath = null)
    {
        $this->evaluationpath = $evaluationpath;

        return $this;
    }

    /**
     * Get evaluationpath.
     *
     * @return string|null
     */
    public function getEvaluationpath()
    {
        return $this->evaluationpath;
    }

    /**
     * Set leaveapply.
     *
     * @param string|null $leaveapply
     *
     * @return RssimyaccountMembers
     */
    public function setLeaveapply($leaveapply = null)
    {
        $this->leaveapply = $leaveapply;

        return $this;
    }

    /**
     * Get leaveapply.
     *
     * @return string|null
     */
    public function getLeaveapply()
    {
        return $this->leaveapply;
    }

    /**
     * Set cl.
     *
     * @param string|null $cl
     *
     * @return RssimyaccountMembers
     */
    public function setCl($cl = null)
    {
        $this->cl = $cl;

        return $this;
    }

    /**
     * Get cl.
     *
     * @return string|null
     */
    public function getCl()
    {
        return $this->cl;
    }

    /**
     * Set sl.
     *
     * @param string|null $sl
     *
     * @return RssimyaccountMembers
     */
    public function setSl($sl = null)
    {
        $this->sl = $sl;

        return $this;
    }

    /**
     * Get sl.
     *
     * @return string|null
     */
    public function getSl()
    {
        return $this->sl;
    }

    /**
     * Set el.
     *
     * @param string|null $el
     *
     * @return RssimyaccountMembers
     */
    public function setEl($el = null)
    {
        $this->el = $el;

        return $this;
    }

    /**
     * Get el.
     *
     * @return string|null
     */
    public function getEl()
    {
        return $this->el;
    }

    /**
     * Set engagement.
     *
     * @param string|null $engagement
     *
     * @return RssimyaccountMembers
     */
    public function setEngagement($engagement = null)
    {
        $this->engagement = $engagement;

        return $this;
    }

    /**
     * Get engagement.
     *
     * @return string|null
     */
    public function getEngagement()
    {
        return $this->engagement;
    }

    /**
     * Set cltaken.
     *
     * @param string|null $cltaken
     *
     * @return RssimyaccountMembers
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
     * @return RssimyaccountMembers
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
     * Set eltaken.
     *
     * @param string|null $eltaken
     *
     * @return RssimyaccountMembers
     */
    public function setEltaken($eltaken = null)
    {
        $this->eltaken = $eltaken;

        return $this;
    }

    /**
     * Get eltaken.
     *
     * @return string|null
     */
    public function getEltaken()
    {
        return $this->eltaken;
    }

    /**
     * Set othtaken.
     *
     * @param string|null $othtaken
     *
     * @return RssimyaccountMembers
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
     * Set clbal.
     *
     * @param string|null $clbal
     *
     * @return RssimyaccountMembers
     */
    public function setClbal($clbal = null)
    {
        $this->clbal = $clbal;

        return $this;
    }

    /**
     * Get clbal.
     *
     * @return string|null
     */
    public function getClbal()
    {
        return $this->clbal;
    }

    /**
     * Set slbal.
     *
     * @param string|null $slbal
     *
     * @return RssimyaccountMembers
     */
    public function setSlbal($slbal = null)
    {
        $this->slbal = $slbal;

        return $this;
    }

    /**
     * Get slbal.
     *
     * @return string|null
     */
    public function getSlbal()
    {
        return $this->slbal;
    }

    /**
     * Set elbal.
     *
     * @param string|null $elbal
     *
     * @return RssimyaccountMembers
     */
    public function setElbal($elbal = null)
    {
        $this->elbal = $elbal;

        return $this;
    }

    /**
     * Get elbal.
     *
     * @return string|null
     */
    public function getElbal()
    {
        return $this->elbal;
    }

    /**
     * Set officialdoc.
     *
     * @param string|null $officialdoc
     *
     * @return RssimyaccountMembers
     */
    public function setOfficialdoc($officialdoc = null)
    {
        $this->officialdoc = $officialdoc;

        return $this;
    }

    /**
     * Get officialdoc.
     *
     * @return string|null
     */
    public function getOfficialdoc()
    {
        return $this->officialdoc;
    }

    /**
     * Set profile.
     *
     * @param string|null $profile
     *
     * @return RssimyaccountMembers
     */
    public function setProfile($profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile.
     *
     * @return string|null
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set filename.
     *
     * @param string|null $filename
     *
     * @return RssimyaccountMembers
     */
    public function setFilename($filename = null)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set fname.
     *
     * @param string|null $fname
     *
     * @return RssimyaccountMembers
     */
    public function setFname($fname = null)
    {
        $this->fname = $fname;

        return $this;
    }

    /**
     * Get fname.
     *
     * @return string|null
     */
    public function getFname()
    {
        return $this->fname;
    }

    /**
     * Set quicklink.
     *
     * @param string|null $quicklink
     *
     * @return RssimyaccountMembers
     */
    public function setQuicklink($quicklink = null)
    {
        $this->quicklink = $quicklink;

        return $this;
    }

    /**
     * Get quicklink.
     *
     * @return string|null
     */
    public function getQuicklink()
    {
        return $this->quicklink;
    }

    /**
     * Set yos.
     *
     * @param string|null $yos
     *
     * @return RssimyaccountMembers
     */
    public function setYos($yos = null)
    {
        $this->yos = $yos;

        return $this;
    }

    /**
     * Get yos.
     *
     * @return string|null
     */
    public function getYos()
    {
        return $this->yos;
    }

    /**
     * Set role.
     *
     * @param string|null $role
     *
     * @return RssimyaccountMembers
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
     * Set originaldoj.
     *
     * @param string|null $originaldoj
     *
     * @return RssimyaccountMembers
     */
    public function setOriginaldoj($originaldoj = null)
    {
        $this->originaldoj = $originaldoj;

        return $this;
    }

    /**
     * Get originaldoj.
     *
     * @return string|null
     */
    public function getOriginaldoj()
    {
        return $this->originaldoj;
    }

    /**
     * Set iddoc.
     *
     * @param string|null $iddoc
     *
     * @return RssimyaccountMembers
     */
    public function setIddoc($iddoc = null)
    {
        $this->iddoc = $iddoc;

        return $this;
    }

    /**
     * Get iddoc.
     *
     * @return string|null
     */
    public function getIddoc()
    {
        return $this->iddoc;
    }

    /**
     * Set vaccination.
     *
     * @param string|null $vaccination
     *
     * @return RssimyaccountMembers
     */
    public function setVaccination($vaccination = null)
    {
        $this->vaccination = $vaccination;

        return $this;
    }

    /**
     * Get vaccination.
     *
     * @return string|null
     */
    public function getVaccination()
    {
        return $this->vaccination;
    }

    /**
     * Set scode.
     *
     * @param string|null $scode
     *
     * @return RssimyaccountMembers
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
     * @return RssimyaccountMembers
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
     * Set questionflag.
     *
     * @param string|null $questionflag
     *
     * @return RssimyaccountMembers
     */
    public function setQuestionflag($questionflag = null)
    {
        $this->questionflag = $questionflag;

        return $this;
    }

    /**
     * Get questionflag.
     *
     * @return string|null
     */
    public function getQuestionflag()
    {
        return $this->questionflag;
    }

    /**
     * Set googlechat.
     *
     * @param string|null $googlechat
     *
     * @return RssimyaccountMembers
     */
    public function setGooglechat($googlechat = null)
    {
        $this->googlechat = $googlechat;

        return $this;
    }

    /**
     * Get googlechat.
     *
     * @return string|null
     */
    public function getGooglechat()
    {
        return $this->googlechat;
    }

    /**
     * Set adjustedleave.
     *
     * @param string|null $adjustedleave
     *
     * @return RssimyaccountMembers
     */
    public function setAdjustedleave($adjustedleave = null)
    {
        $this->adjustedleave = $adjustedleave;

        return $this;
    }

    /**
     * Get adjustedleave.
     *
     * @return string|null
     */
    public function getAdjustedleave()
    {
        return $this->adjustedleave;
    }

    /**
     * Set ipfl.
     *
     * @param string|null $ipfl
     *
     * @return RssimyaccountMembers
     */
    public function setIpfl($ipfl = null)
    {
        $this->ipfl = $ipfl;

        return $this;
    }

    /**
     * Get ipfl.
     *
     * @return string|null
     */
    public function getIpfl()
    {
        return $this->ipfl;
    }

    /**
     * Set eduq.
     *
     * @param string|null $eduq
     *
     * @return RssimyaccountMembers
     */
    public function setEduq($eduq = null)
    {
        $this->eduq = $eduq;

        return $this;
    }

    /**
     * Get eduq.
     *
     * @return string|null
     */
    public function getEduq()
    {
        return $this->eduq;
    }

    /**
     * Set mjorsub.
     *
     * @param string|null $mjorsub
     *
     * @return RssimyaccountMembers
     */
    public function setMjorsub($mjorsub = null)
    {
        $this->mjorsub = $mjorsub;

        return $this;
    }

    /**
     * Get mjorsub.
     *
     * @return string|null
     */
    public function getMjorsub()
    {
        return $this->mjorsub;
    }

    /**
     * Set disc.
     *
     * @param string|null $disc
     *
     * @return RssimyaccountMembers
     */
    public function setDisc($disc = null)
    {
        $this->disc = $disc;

        return $this;
    }

    /**
     * Get disc.
     *
     * @return string|null
     */
    public function getDisc()
    {
        return $this->disc;
    }

    /**
     * Set hbday.
     *
     * @param string|null $hbday
     *
     * @return RssimyaccountMembers
     */
    public function setHbday($hbday = null)
    {
        $this->hbday = $hbday;

        return $this;
    }

    /**
     * Get hbday.
     *
     * @return string|null
     */
    public function getHbday()
    {
        return $this->hbday;
    }

    /**
     * Set onLeave.
     *
     * @param string|null $onLeave
     *
     * @return RssimyaccountMembers
     */
    public function setOnLeave($onLeave = null)
    {
        $this->onLeave = $onLeave;

        return $this;
    }

    /**
     * Get onLeave.
     *
     * @return string|null
     */
    public function getOnLeave()
    {
        return $this->onLeave;
    }

    /**
     * Set attdPending.
     *
     * @param string|null $attdPending
     *
     * @return RssimyaccountMembers
     */
    public function setAttdPending($attdPending = null)
    {
        $this->attdPending = $attdPending;

        return $this;
    }

    /**
     * Get attdPending.
     *
     * @return string|null
     */
    public function getAttdPending()
    {
        return $this->attdPending;
    }

    /**
     * Set approveddate.
     *
     * @param string|null $approveddate
     *
     * @return RssimyaccountMembers
     */
    public function setApproveddate($approveddate = null)
    {
        $this->approveddate = $approveddate;

        return $this;
    }

    /**
     * Get approveddate.
     *
     * @return string|null
     */
    public function getApproveddate()
    {
        return $this->approveddate;
    }

    /**
     * Set password.
     *
     * @param string|null $password
     *
     * @return RssimyaccountMembers
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
     * @return RssimyaccountMembers
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
     * @return RssimyaccountMembers
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
     * Set defaultPassUpdatedBy.
     *
     * @param string|null $defaultPassUpdatedBy
     *
     * @return RssimyaccountMembers
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
     * @return RssimyaccountMembers
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
}
