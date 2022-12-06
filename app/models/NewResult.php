<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * NewResult
 *
 * @ORM\Table(name="new_result")
 * @ORM\Entity
 */
class NewResult
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="new_result_id_seq", allocationSize=1, initialValue=1)
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
     * @ORM\Column(name="dob", type="text", nullable=true)
     */
    private $dob;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hnd", type="text", nullable=true)
     */
    private $hnd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="eng", type="text", nullable=true)
     */
    private $eng;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mth", type="text", nullable=true)
     */
    private $mth;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sce", type="text", nullable=true)
     */
    private $sce;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gka", type="text", nullable=true)
     */
    private $gka;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ssc", type="text", nullable=true)
     */
    private $ssc;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phy", type="text", nullable=true)
     */
    private $phy;

    /**
     * @var string|null
     *
     * @ORM\Column(name="chm", type="text", nullable=true)
     */
    private $chm;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bio", type="text", nullable=true)
     */
    private $bio;

    /**
     * @var string|null
     *
     * @ORM\Column(name="com", type="text", nullable=true)
     */
    private $com;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hd", type="text", nullable=true)
     */
    private $hd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="acc", type="text", nullable=true)
     */
    private $acc;

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
     * @ORM\Column(name="attd", type="text", nullable=true)
     */
    private $attd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="examname", type="text", nullable=true)
     */
    private $examname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullmarks", type="text", nullable=true)
     */
    private $fullmarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="month", type="text", nullable=true)
     */
    private $month;

    /**
     * @var string|null
     *
     * @ORM\Column(name="language1", type="text", nullable=true)
     */
    private $language1;



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
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * Set dob.
     *
     * @param string|null $dob
     *
     * @return NewResult
     */
    public function setDob($dob = null)
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * Get dob.
     *
     * @return string|null
     */
    public function getDob()
    {
        return $this->dob;
    }

    /**
     * Set hnd.
     *
     * @param string|null $hnd
     *
     * @return NewResult
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
     * Set eng.
     *
     * @param string|null $eng
     *
     * @return NewResult
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
     * Set mth.
     *
     * @param string|null $mth
     *
     * @return NewResult
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
     * Set sce.
     *
     * @param string|null $sce
     *
     * @return NewResult
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
     * Set gka.
     *
     * @param string|null $gka
     *
     * @return NewResult
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
     * Set ssc.
     *
     * @param string|null $ssc
     *
     * @return NewResult
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
     * Set phy.
     *
     * @param string|null $phy
     *
     * @return NewResult
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
     * Set chm.
     *
     * @param string|null $chm
     *
     * @return NewResult
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
     * Set bio.
     *
     * @param string|null $bio
     *
     * @return NewResult
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
     * Set com.
     *
     * @param string|null $com
     *
     * @return NewResult
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
     * Set hd.
     *
     * @param string|null $hd
     *
     * @return NewResult
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
     * Set acc.
     *
     * @param string|null $acc
     *
     * @return NewResult
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
     * Set pt.
     *
     * @param string|null $pt
     *
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * @return NewResult
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
     * Set attd.
     *
     * @param string|null $attd
     *
     * @return NewResult
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
     * Set examname.
     *
     * @param string|null $examname
     *
     * @return NewResult
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
     * Set fullmarks.
     *
     * @param string|null $fullmarks
     *
     * @return NewResult
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
     * Set month.
     *
     * @param string|null $month
     *
     * @return NewResult
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
     * Set language1.
     *
     * @param string|null $language1
     *
     * @return NewResult
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
}
