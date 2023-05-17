<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Result
 *
 * @ORM\Table(name="result")
 * @ORM\Entity
 */
class Result
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="result_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="studentid", type="text", nullable=true)
     */
    private $studentid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="class", type="text", nullable=true)
     */
    private $class;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hnd_o", type="text", nullable=true)
     */
    private $hndO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hnd", type="text", nullable=true)
     */
    private $hnd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="eng_o", type="text", nullable=true)
     */
    private $engO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="eng", type="text", nullable=true)
     */
    private $eng;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mth_o", type="text", nullable=true)
     */
    private $mthO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mth", type="text", nullable=true)
     */
    private $mth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sce_o", type="text", nullable=true)
     */
    private $sceO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sce", type="text", nullable=true)
     */
    private $sce;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gka_o", type="text", nullable=true)
     */
    private $gkaO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gka", type="text", nullable=true)
     */
    private $gka;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ssc_o", type="text", nullable=true)
     */
    private $sscO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ssc", type="text", nullable=true)
     */
    private $ssc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phy_o", type="text", nullable=true)
     */
    private $phyO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phy", type="text", nullable=true)
     */
    private $phy;

    /**
     * @var string|null
     *
     * @ORM\Column(name="chm_o", type="text", nullable=true)
     */
    private $chmO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="chm", type="text", nullable=true)
     */
    private $chm;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bio_o", type="text", nullable=true)
     */
    private $bioO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bio", type="text", nullable=true)
     */
    private $bio;

    /**
     * @var string|null
     *
     * @ORM\Column(name="com_o", type="text", nullable=true)
     */
    private $comO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="com", type="text", nullable=true)
     */
    private $com;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hd_o", type="text", nullable=true)
     */
    private $hdO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hd", type="text", nullable=true)
     */
    private $hd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="acc_o", type="text", nullable=true)
     */
    private $accO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="acc", type="text", nullable=true)
     */
    private $acc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pt_o", type="text", nullable=true)
     */
    private $ptO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pt", type="text", nullable=true)
     */
    private $pt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="total", type="text", nullable=true)
     */
    private $total;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mm", type="text", nullable=true)
     */
    private $mm;

    /**
     * @var string|null
     *
     * @ORM\Column(name="op", type="text", nullable=true)
     */
    private $op;

    /**
     * @var string|null
     *
     * @ORM\Column(name="grade", type="text", nullable=true)
     */
    private $grade;

    /**
     * @var string|null
     *
     * @ORM\Column(name="result", type="text", nullable=true)
     */
    private $result;

    /**
     * @var string|null
     *
     * @ORM\Column(name="position", type="text", nullable=true)
     */
    private $position;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullmarks_o", type="text", nullable=true)
     */
    private $fullmarksO;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullmarks", type="text", nullable=true)
     */
    private $fullmarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="examname", type="text", nullable=true)
     */
    private $examname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="language1", type="text", nullable=true)
     */
    private $language1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="attd", type="text", nullable=true)
     */
    private $attd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="month", type="text", nullable=true)
     */
    private $month;

    /**
     * @var string|null
     *
     * @ORM\Column(name="academicyear", type="text", nullable=true)
     */
    private $academicyear;



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
     * Set name.
     *
     * @param string|null $name
     *
     * @return Result
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set studentid.
     *
     * @param string|null $studentid
     *
     * @return Result
     */
    public function setStudentid($studentid = null)
    {
        $this->studentid = $studentid;

        return $this;
    }

    /**
     * Get studentid.
     *
     * @return string|null
     */
    public function getStudentid()
    {
        return $this->studentid;
    }

    /**
     * Set category.
     *
     * @param string|null $category
     *
     * @return Result
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
     * Set class.
     *
     * @param string|null $class
     *
     * @return Result
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
     * Set hndO.
     *
     * @param string|null $hndO
     *
     * @return Result
     */
    public function setHndO($hndO = null)
    {
        $this->hndO = $hndO;

        return $this;
    }

    /**
     * Get hndO.
     *
     * @return string|null
     */
    public function getHndO()
    {
        return $this->hndO;
    }

    /**
     * Set hnd.
     *
     * @param string|null $hnd
     *
     * @return Result
     */
    public function setHnd($hnd = null)
    {
        $this->hnd = $hnd;

        return $this;
    }

    /**
     * Get hnd.
     *
     * @return string|null
     */
    public function getHnd()
    {
        return $this->hnd;
    }

    /**
     * Set engO.
     *
     * @param string|null $engO
     *
     * @return Result
     */
    public function setEngO($engO = null)
    {
        $this->engO = $engO;

        return $this;
    }

    /**
     * Get engO.
     *
     * @return string|null
     */
    public function getEngO()
    {
        return $this->engO;
    }

    /**
     * Set eng.
     *
     * @param string|null $eng
     *
     * @return Result
     */
    public function setEng($eng = null)
    {
        $this->eng = $eng;

        return $this;
    }

    /**
     * Get eng.
     *
     * @return string|null
     */
    public function getEng()
    {
        return $this->eng;
    }

    /**
     * Set mthO.
     *
     * @param string|null $mthO
     *
     * @return Result
     */
    public function setMthO($mthO = null)
    {
        $this->mthO = $mthO;

        return $this;
    }

    /**
     * Get mthO.
     *
     * @return string|null
     */
    public function getMthO()
    {
        return $this->mthO;
    }

    /**
     * Set mth.
     *
     * @param string|null $mth
     *
     * @return Result
     */
    public function setMth($mth = null)
    {
        $this->mth = $mth;

        return $this;
    }

    /**
     * Get mth.
     *
     * @return string|null
     */
    public function getMth()
    {
        return $this->mth;
    }

    /**
     * Set sceO.
     *
     * @param string|null $sceO
     *
     * @return Result
     */
    public function setSceO($sceO = null)
    {
        $this->sceO = $sceO;

        return $this;
    }

    /**
     * Get sceO.
     *
     * @return string|null
     */
    public function getSceO()
    {
        return $this->sceO;
    }

    /**
     * Set sce.
     *
     * @param string|null $sce
     *
     * @return Result
     */
    public function setSce($sce = null)
    {
        $this->sce = $sce;

        return $this;
    }

    /**
     * Get sce.
     *
     * @return string|null
     */
    public function getSce()
    {
        return $this->sce;
    }

    /**
     * Set gkaO.
     *
     * @param string|null $gkaO
     *
     * @return Result
     */
    public function setGkaO($gkaO = null)
    {
        $this->gkaO = $gkaO;

        return $this;
    }

    /**
     * Get gkaO.
     *
     * @return string|null
     */
    public function getGkaO()
    {
        return $this->gkaO;
    }

    /**
     * Set gka.
     *
     * @param string|null $gka
     *
     * @return Result
     */
    public function setGka($gka = null)
    {
        $this->gka = $gka;

        return $this;
    }

    /**
     * Get gka.
     *
     * @return string|null
     */
    public function getGka()
    {
        return $this->gka;
    }

    /**
     * Set sscO.
     *
     * @param string|null $sscO
     *
     * @return Result
     */
    public function setSscO($sscO = null)
    {
        $this->sscO = $sscO;

        return $this;
    }

    /**
     * Get sscO.
     *
     * @return string|null
     */
    public function getSscO()
    {
        return $this->sscO;
    }

    /**
     * Set ssc.
     *
     * @param string|null $ssc
     *
     * @return Result
     */
    public function setSsc($ssc = null)
    {
        $this->ssc = $ssc;

        return $this;
    }

    /**
     * Get ssc.
     *
     * @return string|null
     */
    public function getSsc()
    {
        return $this->ssc;
    }

    /**
     * Set phyO.
     *
     * @param string|null $phyO
     *
     * @return Result
     */
    public function setPhyO($phyO = null)
    {
        $this->phyO = $phyO;

        return $this;
    }

    /**
     * Get phyO.
     *
     * @return string|null
     */
    public function getPhyO()
    {
        return $this->phyO;
    }

    /**
     * Set phy.
     *
     * @param string|null $phy
     *
     * @return Result
     */
    public function setPhy($phy = null)
    {
        $this->phy = $phy;

        return $this;
    }

    /**
     * Get phy.
     *
     * @return string|null
     */
    public function getPhy()
    {
        return $this->phy;
    }

    /**
     * Set chmO.
     *
     * @param string|null $chmO
     *
     * @return Result
     */
    public function setChmO($chmO = null)
    {
        $this->chmO = $chmO;

        return $this;
    }

    /**
     * Get chmO.
     *
     * @return string|null
     */
    public function getChmO()
    {
        return $this->chmO;
    }

    /**
     * Set chm.
     *
     * @param string|null $chm
     *
     * @return Result
     */
    public function setChm($chm = null)
    {
        $this->chm = $chm;

        return $this;
    }

    /**
     * Get chm.
     *
     * @return string|null
     */
    public function getChm()
    {
        return $this->chm;
    }

    /**
     * Set bioO.
     *
     * @param string|null $bioO
     *
     * @return Result
     */
    public function setBioO($bioO = null)
    {
        $this->bioO = $bioO;

        return $this;
    }

    /**
     * Get bioO.
     *
     * @return string|null
     */
    public function getBioO()
    {
        return $this->bioO;
    }

    /**
     * Set bio.
     *
     * @param string|null $bio
     *
     * @return Result
     */
    public function setBio($bio = null)
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * Get bio.
     *
     * @return string|null
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * Set comO.
     *
     * @param string|null $comO
     *
     * @return Result
     */
    public function setComO($comO = null)
    {
        $this->comO = $comO;

        return $this;
    }

    /**
     * Get comO.
     *
     * @return string|null
     */
    public function getComO()
    {
        return $this->comO;
    }

    /**
     * Set com.
     *
     * @param string|null $com
     *
     * @return Result
     */
    public function setCom($com = null)
    {
        $this->com = $com;

        return $this;
    }

    /**
     * Get com.
     *
     * @return string|null
     */
    public function getCom()
    {
        return $this->com;
    }

    /**
     * Set hdO.
     *
     * @param string|null $hdO
     *
     * @return Result
     */
    public function setHdO($hdO = null)
    {
        $this->hdO = $hdO;

        return $this;
    }

    /**
     * Get hdO.
     *
     * @return string|null
     */
    public function getHdO()
    {
        return $this->hdO;
    }

    /**
     * Set hd.
     *
     * @param string|null $hd
     *
     * @return Result
     */
    public function setHd($hd = null)
    {
        $this->hd = $hd;

        return $this;
    }

    /**
     * Get hd.
     *
     * @return string|null
     */
    public function getHd()
    {
        return $this->hd;
    }

    /**
     * Set accO.
     *
     * @param string|null $accO
     *
     * @return Result
     */
    public function setAccO($accO = null)
    {
        $this->accO = $accO;

        return $this;
    }

    /**
     * Get accO.
     *
     * @return string|null
     */
    public function getAccO()
    {
        return $this->accO;
    }

    /**
     * Set acc.
     *
     * @param string|null $acc
     *
     * @return Result
     */
    public function setAcc($acc = null)
    {
        $this->acc = $acc;

        return $this;
    }

    /**
     * Get acc.
     *
     * @return string|null
     */
    public function getAcc()
    {
        return $this->acc;
    }

    /**
     * Set ptO.
     *
     * @param string|null $ptO
     *
     * @return Result
     */
    public function setPtO($ptO = null)
    {
        $this->ptO = $ptO;

        return $this;
    }

    /**
     * Get ptO.
     *
     * @return string|null
     */
    public function getPtO()
    {
        return $this->ptO;
    }

    /**
     * Set pt.
     *
     * @param string|null $pt
     *
     * @return Result
     */
    public function setPt($pt = null)
    {
        $this->pt = $pt;

        return $this;
    }

    /**
     * Get pt.
     *
     * @return string|null
     */
    public function getPt()
    {
        return $this->pt;
    }

    /**
     * Set total.
     *
     * @param string|null $total
     *
     * @return Result
     */
    public function setTotal($total = null)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total.
     *
     * @return string|null
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set mm.
     *
     * @param string|null $mm
     *
     * @return Result
     */
    public function setMm($mm = null)
    {
        $this->mm = $mm;

        return $this;
    }

    /**
     * Get mm.
     *
     * @return string|null
     */
    public function getMm()
    {
        return $this->mm;
    }

    /**
     * Set op.
     *
     * @param string|null $op
     *
     * @return Result
     */
    public function setOp($op = null)
    {
        $this->op = $op;

        return $this;
    }

    /**
     * Get op.
     *
     * @return string|null
     */
    public function getOp()
    {
        return $this->op;
    }

    /**
     * Set grade.
     *
     * @param string|null $grade
     *
     * @return Result
     */
    public function setGrade($grade = null)
    {
        $this->grade = $grade;

        return $this;
    }

    /**
     * Get grade.
     *
     * @return string|null
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Set result.
     *
     * @param string|null $result
     *
     * @return Result
     */
    public function setResult($result = null)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result.
     *
     * @return string|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set position.
     *
     * @param string|null $position
     *
     * @return Result
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
     * Set fullmarksO.
     *
     * @param string|null $fullmarksO
     *
     * @return Result
     */
    public function setFullmarksO($fullmarksO = null)
    {
        $this->fullmarksO = $fullmarksO;

        return $this;
    }

    /**
     * Get fullmarksO.
     *
     * @return string|null
     */
    public function getFullmarksO()
    {
        return $this->fullmarksO;
    }

    /**
     * Set fullmarks.
     *
     * @param string|null $fullmarks
     *
     * @return Result
     */
    public function setFullmarks($fullmarks = null)
    {
        $this->fullmarks = $fullmarks;

        return $this;
    }

    /**
     * Get fullmarks.
     *
     * @return string|null
     */
    public function getFullmarks()
    {
        return $this->fullmarks;
    }

    /**
     * Set examname.
     *
     * @param string|null $examname
     *
     * @return Result
     */
    public function setExamname($examname = null)
    {
        $this->examname = $examname;

        return $this;
    }

    /**
     * Get examname.
     *
     * @return string|null
     */
    public function getExamname()
    {
        return $this->examname;
    }

    /**
     * Set language1.
     *
     * @param string|null $language1
     *
     * @return Result
     */
    public function setLanguage1($language1 = null)
    {
        $this->language1 = $language1;

        return $this;
    }

    /**
     * Get language1.
     *
     * @return string|null
     */
    public function getLanguage1()
    {
        return $this->language1;
    }

    /**
     * Set attd.
     *
     * @param string|null $attd
     *
     * @return Result
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
     * Set month.
     *
     * @param string|null $month
     *
     * @return Result
     */
    public function setMonth($month = null)
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get month.
     *
     * @return string|null
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set academicyear.
     *
     * @param string|null $academicyear
     *
     * @return Result
     */
    public function setAcademicyear($academicyear = null)
    {
        $this->academicyear = $academicyear;

        return $this;
    }

    /**
     * Get academicyear.
     *
     * @return string|null
     */
    public function getAcademicyear()
    {
        return $this->academicyear;
    }
}
