<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Rolebasedgoal
 *
 * @ORM\Table(name="rolebasedgoal")
 * @ORM\Entity
 */
class Rolebasedgoal
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="rolebasedgoal_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="role_search", type="text", nullable=true)
     */
    private $roleSearch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_1", type="text", nullable=true)
     */
    private $parameter1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_1", type="text", nullable=true)
     */
    private $expectation1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_1", type="integer", nullable=true)
     */
    private $maxRating1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_2", type="text", nullable=true)
     */
    private $parameter2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_2", type="text", nullable=true)
     */
    private $expectation2;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_2", type="integer", nullable=true)
     */
    private $maxRating2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_3", type="text", nullable=true)
     */
    private $parameter3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_3", type="text", nullable=true)
     */
    private $expectation3;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_3", type="integer", nullable=true)
     */
    private $maxRating3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_4", type="text", nullable=true)
     */
    private $parameter4;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_4", type="text", nullable=true)
     */
    private $expectation4;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_4", type="integer", nullable=true)
     */
    private $maxRating4;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_5", type="text", nullable=true)
     */
    private $parameter5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_5", type="text", nullable=true)
     */
    private $expectation5;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_5", type="integer", nullable=true)
     */
    private $maxRating5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_6", type="text", nullable=true)
     */
    private $parameter6;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_6", type="text", nullable=true)
     */
    private $expectation6;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_6", type="integer", nullable=true)
     */
    private $maxRating6;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_7", type="text", nullable=true)
     */
    private $parameter7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_7", type="text", nullable=true)
     */
    private $expectation7;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_7", type="integer", nullable=true)
     */
    private $maxRating7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_8", type="text", nullable=true)
     */
    private $parameter8;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_8", type="text", nullable=true)
     */
    private $expectation8;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_8", type="integer", nullable=true)
     */
    private $maxRating8;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_9", type="text", nullable=true)
     */
    private $parameter9;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_9", type="text", nullable=true)
     */
    private $expectation9;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_9", type="integer", nullable=true)
     */
    private $maxRating9;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_10", type="text", nullable=true)
     */
    private $parameter10;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_10", type="text", nullable=true)
     */
    private $expectation10;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_10", type="integer", nullable=true)
     */
    private $maxRating10;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_11", type="text", nullable=true)
     */
    private $parameter11;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_11", type="text", nullable=true)
     */
    private $expectation11;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_11", type="integer", nullable=true)
     */
    private $maxRating11;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_12", type="text", nullable=true)
     */
    private $parameter12;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_12", type="text", nullable=true)
     */
    private $expectation12;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_12", type="integer", nullable=true)
     */
    private $maxRating12;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_13", type="text", nullable=true)
     */
    private $parameter13;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_13", type="text", nullable=true)
     */
    private $expectation13;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_13", type="integer", nullable=true)
     */
    private $maxRating13;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_14", type="text", nullable=true)
     */
    private $parameter14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_14", type="text", nullable=true)
     */
    private $expectation14;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_14", type="integer", nullable=true)
     */
    private $maxRating14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_15", type="text", nullable=true)
     */
    private $parameter15;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_15", type="text", nullable=true)
     */
    private $expectation15;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_15", type="integer", nullable=true)
     */
    private $maxRating15;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_16", type="text", nullable=true)
     */
    private $parameter16;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_16", type="text", nullable=true)
     */
    private $expectation16;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_16", type="integer", nullable=true)
     */
    private $maxRating16;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_17", type="text", nullable=true)
     */
    private $parameter17;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_17", type="text", nullable=true)
     */
    private $expectation17;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_17", type="integer", nullable=true)
     */
    private $maxRating17;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_18", type="text", nullable=true)
     */
    private $parameter18;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_18", type="text", nullable=true)
     */
    private $expectation18;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_18", type="integer", nullable=true)
     */
    private $maxRating18;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_19", type="text", nullable=true)
     */
    private $parameter19;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_19", type="text", nullable=true)
     */
    private $expectation19;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_19", type="integer", nullable=true)
     */
    private $maxRating19;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_20", type="text", nullable=true)
     */
    private $parameter20;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_20", type="text", nullable=true)
     */
    private $expectation20;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_20", type="integer", nullable=true)
     */
    private $maxRating20;



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
     * Set roleSearch.
     *
     * @param string|null $roleSearch
     *
     * @return Rolebasedgoal
     */
    public function setRoleSearch($roleSearch = null)
    {
        $this->roleSearch = $roleSearch;

        return $this;
    }

    /**
     * Get roleSearch.
     *
     * @return string|null
     */
    public function getRoleSearch()
    {
        return $this->roleSearch;
    }

    /**
     * Set parameter1.
     *
     * @param string|null $parameter1
     *
     * @return Rolebasedgoal
     */
    public function setParameter1($parameter1 = null)
    {
        $this->parameter1 = $parameter1;

        return $this;
    }

    /**
     * Get parameter1.
     *
     * @return string|null
     */
    public function getParameter1()
    {
        return $this->parameter1;
    }

    /**
     * Set expectation1.
     *
     * @param string|null $expectation1
     *
     * @return Rolebasedgoal
     */
    public function setExpectation1($expectation1 = null)
    {
        $this->expectation1 = $expectation1;

        return $this;
    }

    /**
     * Get expectation1.
     *
     * @return string|null
     */
    public function getExpectation1()
    {
        return $this->expectation1;
    }

    /**
     * Set maxRating1.
     *
     * @param int|null $maxRating1
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating1($maxRating1 = null)
    {
        $this->maxRating1 = $maxRating1;

        return $this;
    }

    /**
     * Get maxRating1.
     *
     * @return int|null
     */
    public function getMaxRating1()
    {
        return $this->maxRating1;
    }

    /**
     * Set parameter2.
     *
     * @param string|null $parameter2
     *
     * @return Rolebasedgoal
     */
    public function setParameter2($parameter2 = null)
    {
        $this->parameter2 = $parameter2;

        return $this;
    }

    /**
     * Get parameter2.
     *
     * @return string|null
     */
    public function getParameter2()
    {
        return $this->parameter2;
    }

    /**
     * Set expectation2.
     *
     * @param string|null $expectation2
     *
     * @return Rolebasedgoal
     */
    public function setExpectation2($expectation2 = null)
    {
        $this->expectation2 = $expectation2;

        return $this;
    }

    /**
     * Get expectation2.
     *
     * @return string|null
     */
    public function getExpectation2()
    {
        return $this->expectation2;
    }

    /**
     * Set maxRating2.
     *
     * @param int|null $maxRating2
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating2($maxRating2 = null)
    {
        $this->maxRating2 = $maxRating2;

        return $this;
    }

    /**
     * Get maxRating2.
     *
     * @return int|null
     */
    public function getMaxRating2()
    {
        return $this->maxRating2;
    }

    /**
     * Set parameter3.
     *
     * @param string|null $parameter3
     *
     * @return Rolebasedgoal
     */
    public function setParameter3($parameter3 = null)
    {
        $this->parameter3 = $parameter3;

        return $this;
    }

    /**
     * Get parameter3.
     *
     * @return string|null
     */
    public function getParameter3()
    {
        return $this->parameter3;
    }

    /**
     * Set expectation3.
     *
     * @param string|null $expectation3
     *
     * @return Rolebasedgoal
     */
    public function setExpectation3($expectation3 = null)
    {
        $this->expectation3 = $expectation3;

        return $this;
    }

    /**
     * Get expectation3.
     *
     * @return string|null
     */
    public function getExpectation3()
    {
        return $this->expectation3;
    }

    /**
     * Set maxRating3.
     *
     * @param int|null $maxRating3
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating3($maxRating3 = null)
    {
        $this->maxRating3 = $maxRating3;

        return $this;
    }

    /**
     * Get maxRating3.
     *
     * @return int|null
     */
    public function getMaxRating3()
    {
        return $this->maxRating3;
    }

    /**
     * Set parameter4.
     *
     * @param string|null $parameter4
     *
     * @return Rolebasedgoal
     */
    public function setParameter4($parameter4 = null)
    {
        $this->parameter4 = $parameter4;

        return $this;
    }

    /**
     * Get parameter4.
     *
     * @return string|null
     */
    public function getParameter4()
    {
        return $this->parameter4;
    }

    /**
     * Set expectation4.
     *
     * @param string|null $expectation4
     *
     * @return Rolebasedgoal
     */
    public function setExpectation4($expectation4 = null)
    {
        $this->expectation4 = $expectation4;

        return $this;
    }

    /**
     * Get expectation4.
     *
     * @return string|null
     */
    public function getExpectation4()
    {
        return $this->expectation4;
    }

    /**
     * Set maxRating4.
     *
     * @param int|null $maxRating4
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating4($maxRating4 = null)
    {
        $this->maxRating4 = $maxRating4;

        return $this;
    }

    /**
     * Get maxRating4.
     *
     * @return int|null
     */
    public function getMaxRating4()
    {
        return $this->maxRating4;
    }

    /**
     * Set parameter5.
     *
     * @param string|null $parameter5
     *
     * @return Rolebasedgoal
     */
    public function setParameter5($parameter5 = null)
    {
        $this->parameter5 = $parameter5;

        return $this;
    }

    /**
     * Get parameter5.
     *
     * @return string|null
     */
    public function getParameter5()
    {
        return $this->parameter5;
    }

    /**
     * Set expectation5.
     *
     * @param string|null $expectation5
     *
     * @return Rolebasedgoal
     */
    public function setExpectation5($expectation5 = null)
    {
        $this->expectation5 = $expectation5;

        return $this;
    }

    /**
     * Get expectation5.
     *
     * @return string|null
     */
    public function getExpectation5()
    {
        return $this->expectation5;
    }

    /**
     * Set maxRating5.
     *
     * @param int|null $maxRating5
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating5($maxRating5 = null)
    {
        $this->maxRating5 = $maxRating5;

        return $this;
    }

    /**
     * Get maxRating5.
     *
     * @return int|null
     */
    public function getMaxRating5()
    {
        return $this->maxRating5;
    }

    /**
     * Set parameter6.
     *
     * @param string|null $parameter6
     *
     * @return Rolebasedgoal
     */
    public function setParameter6($parameter6 = null)
    {
        $this->parameter6 = $parameter6;

        return $this;
    }

    /**
     * Get parameter6.
     *
     * @return string|null
     */
    public function getParameter6()
    {
        return $this->parameter6;
    }

    /**
     * Set expectation6.
     *
     * @param string|null $expectation6
     *
     * @return Rolebasedgoal
     */
    public function setExpectation6($expectation6 = null)
    {
        $this->expectation6 = $expectation6;

        return $this;
    }

    /**
     * Get expectation6.
     *
     * @return string|null
     */
    public function getExpectation6()
    {
        return $this->expectation6;
    }

    /**
     * Set maxRating6.
     *
     * @param int|null $maxRating6
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating6($maxRating6 = null)
    {
        $this->maxRating6 = $maxRating6;

        return $this;
    }

    /**
     * Get maxRating6.
     *
     * @return int|null
     */
    public function getMaxRating6()
    {
        return $this->maxRating6;
    }

    /**
     * Set parameter7.
     *
     * @param string|null $parameter7
     *
     * @return Rolebasedgoal
     */
    public function setParameter7($parameter7 = null)
    {
        $this->parameter7 = $parameter7;

        return $this;
    }

    /**
     * Get parameter7.
     *
     * @return string|null
     */
    public function getParameter7()
    {
        return $this->parameter7;
    }

    /**
     * Set expectation7.
     *
     * @param string|null $expectation7
     *
     * @return Rolebasedgoal
     */
    public function setExpectation7($expectation7 = null)
    {
        $this->expectation7 = $expectation7;

        return $this;
    }

    /**
     * Get expectation7.
     *
     * @return string|null
     */
    public function getExpectation7()
    {
        return $this->expectation7;
    }

    /**
     * Set maxRating7.
     *
     * @param int|null $maxRating7
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating7($maxRating7 = null)
    {
        $this->maxRating7 = $maxRating7;

        return $this;
    }

    /**
     * Get maxRating7.
     *
     * @return int|null
     */
    public function getMaxRating7()
    {
        return $this->maxRating7;
    }

    /**
     * Set parameter8.
     *
     * @param string|null $parameter8
     *
     * @return Rolebasedgoal
     */
    public function setParameter8($parameter8 = null)
    {
        $this->parameter8 = $parameter8;

        return $this;
    }

    /**
     * Get parameter8.
     *
     * @return string|null
     */
    public function getParameter8()
    {
        return $this->parameter8;
    }

    /**
     * Set expectation8.
     *
     * @param string|null $expectation8
     *
     * @return Rolebasedgoal
     */
    public function setExpectation8($expectation8 = null)
    {
        $this->expectation8 = $expectation8;

        return $this;
    }

    /**
     * Get expectation8.
     *
     * @return string|null
     */
    public function getExpectation8()
    {
        return $this->expectation8;
    }

    /**
     * Set maxRating8.
     *
     * @param int|null $maxRating8
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating8($maxRating8 = null)
    {
        $this->maxRating8 = $maxRating8;

        return $this;
    }

    /**
     * Get maxRating8.
     *
     * @return int|null
     */
    public function getMaxRating8()
    {
        return $this->maxRating8;
    }

    /**
     * Set parameter9.
     *
     * @param string|null $parameter9
     *
     * @return Rolebasedgoal
     */
    public function setParameter9($parameter9 = null)
    {
        $this->parameter9 = $parameter9;

        return $this;
    }

    /**
     * Get parameter9.
     *
     * @return string|null
     */
    public function getParameter9()
    {
        return $this->parameter9;
    }

    /**
     * Set expectation9.
     *
     * @param string|null $expectation9
     *
     * @return Rolebasedgoal
     */
    public function setExpectation9($expectation9 = null)
    {
        $this->expectation9 = $expectation9;

        return $this;
    }

    /**
     * Get expectation9.
     *
     * @return string|null
     */
    public function getExpectation9()
    {
        return $this->expectation9;
    }

    /**
     * Set maxRating9.
     *
     * @param int|null $maxRating9
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating9($maxRating9 = null)
    {
        $this->maxRating9 = $maxRating9;

        return $this;
    }

    /**
     * Get maxRating9.
     *
     * @return int|null
     */
    public function getMaxRating9()
    {
        return $this->maxRating9;
    }

    /**
     * Set parameter10.
     *
     * @param string|null $parameter10
     *
     * @return Rolebasedgoal
     */
    public function setParameter10($parameter10 = null)
    {
        $this->parameter10 = $parameter10;

        return $this;
    }

    /**
     * Get parameter10.
     *
     * @return string|null
     */
    public function getParameter10()
    {
        return $this->parameter10;
    }

    /**
     * Set expectation10.
     *
     * @param string|null $expectation10
     *
     * @return Rolebasedgoal
     */
    public function setExpectation10($expectation10 = null)
    {
        $this->expectation10 = $expectation10;

        return $this;
    }

    /**
     * Get expectation10.
     *
     * @return string|null
     */
    public function getExpectation10()
    {
        return $this->expectation10;
    }

    /**
     * Set maxRating10.
     *
     * @param int|null $maxRating10
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating10($maxRating10 = null)
    {
        $this->maxRating10 = $maxRating10;

        return $this;
    }

    /**
     * Get maxRating10.
     *
     * @return int|null
     */
    public function getMaxRating10()
    {
        return $this->maxRating10;
    }

    /**
     * Set parameter11.
     *
     * @param string|null $parameter11
     *
     * @return Rolebasedgoal
     */
    public function setParameter11($parameter11 = null)
    {
        $this->parameter11 = $parameter11;

        return $this;
    }

    /**
     * Get parameter11.
     *
     * @return string|null
     */
    public function getParameter11()
    {
        return $this->parameter11;
    }

    /**
     * Set expectation11.
     *
     * @param string|null $expectation11
     *
     * @return Rolebasedgoal
     */
    public function setExpectation11($expectation11 = null)
    {
        $this->expectation11 = $expectation11;

        return $this;
    }

    /**
     * Get expectation11.
     *
     * @return string|null
     */
    public function getExpectation11()
    {
        return $this->expectation11;
    }

    /**
     * Set maxRating11.
     *
     * @param int|null $maxRating11
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating11($maxRating11 = null)
    {
        $this->maxRating11 = $maxRating11;

        return $this;
    }

    /**
     * Get maxRating11.
     *
     * @return int|null
     */
    public function getMaxRating11()
    {
        return $this->maxRating11;
    }

    /**
     * Set parameter12.
     *
     * @param string|null $parameter12
     *
     * @return Rolebasedgoal
     */
    public function setParameter12($parameter12 = null)
    {
        $this->parameter12 = $parameter12;

        return $this;
    }

    /**
     * Get parameter12.
     *
     * @return string|null
     */
    public function getParameter12()
    {
        return $this->parameter12;
    }

    /**
     * Set expectation12.
     *
     * @param string|null $expectation12
     *
     * @return Rolebasedgoal
     */
    public function setExpectation12($expectation12 = null)
    {
        $this->expectation12 = $expectation12;

        return $this;
    }

    /**
     * Get expectation12.
     *
     * @return string|null
     */
    public function getExpectation12()
    {
        return $this->expectation12;
    }

    /**
     * Set maxRating12.
     *
     * @param int|null $maxRating12
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating12($maxRating12 = null)
    {
        $this->maxRating12 = $maxRating12;

        return $this;
    }

    /**
     * Get maxRating12.
     *
     * @return int|null
     */
    public function getMaxRating12()
    {
        return $this->maxRating12;
    }

    /**
     * Set parameter13.
     *
     * @param string|null $parameter13
     *
     * @return Rolebasedgoal
     */
    public function setParameter13($parameter13 = null)
    {
        $this->parameter13 = $parameter13;

        return $this;
    }

    /**
     * Get parameter13.
     *
     * @return string|null
     */
    public function getParameter13()
    {
        return $this->parameter13;
    }

    /**
     * Set expectation13.
     *
     * @param string|null $expectation13
     *
     * @return Rolebasedgoal
     */
    public function setExpectation13($expectation13 = null)
    {
        $this->expectation13 = $expectation13;

        return $this;
    }

    /**
     * Get expectation13.
     *
     * @return string|null
     */
    public function getExpectation13()
    {
        return $this->expectation13;
    }

    /**
     * Set maxRating13.
     *
     * @param int|null $maxRating13
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating13($maxRating13 = null)
    {
        $this->maxRating13 = $maxRating13;

        return $this;
    }

    /**
     * Get maxRating13.
     *
     * @return int|null
     */
    public function getMaxRating13()
    {
        return $this->maxRating13;
    }

    /**
     * Set parameter14.
     *
     * @param string|null $parameter14
     *
     * @return Rolebasedgoal
     */
    public function setParameter14($parameter14 = null)
    {
        $this->parameter14 = $parameter14;

        return $this;
    }

    /**
     * Get parameter14.
     *
     * @return string|null
     */
    public function getParameter14()
    {
        return $this->parameter14;
    }

    /**
     * Set expectation14.
     *
     * @param string|null $expectation14
     *
     * @return Rolebasedgoal
     */
    public function setExpectation14($expectation14 = null)
    {
        $this->expectation14 = $expectation14;

        return $this;
    }

    /**
     * Get expectation14.
     *
     * @return string|null
     */
    public function getExpectation14()
    {
        return $this->expectation14;
    }

    /**
     * Set maxRating14.
     *
     * @param int|null $maxRating14
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating14($maxRating14 = null)
    {
        $this->maxRating14 = $maxRating14;

        return $this;
    }

    /**
     * Get maxRating14.
     *
     * @return int|null
     */
    public function getMaxRating14()
    {
        return $this->maxRating14;
    }

    /**
     * Set parameter15.
     *
     * @param string|null $parameter15
     *
     * @return Rolebasedgoal
     */
    public function setParameter15($parameter15 = null)
    {
        $this->parameter15 = $parameter15;

        return $this;
    }

    /**
     * Get parameter15.
     *
     * @return string|null
     */
    public function getParameter15()
    {
        return $this->parameter15;
    }

    /**
     * Set expectation15.
     *
     * @param string|null $expectation15
     *
     * @return Rolebasedgoal
     */
    public function setExpectation15($expectation15 = null)
    {
        $this->expectation15 = $expectation15;

        return $this;
    }

    /**
     * Get expectation15.
     *
     * @return string|null
     */
    public function getExpectation15()
    {
        return $this->expectation15;
    }

    /**
     * Set maxRating15.
     *
     * @param int|null $maxRating15
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating15($maxRating15 = null)
    {
        $this->maxRating15 = $maxRating15;

        return $this;
    }

    /**
     * Get maxRating15.
     *
     * @return int|null
     */
    public function getMaxRating15()
    {
        return $this->maxRating15;
    }

    /**
     * Set parameter16.
     *
     * @param string|null $parameter16
     *
     * @return Rolebasedgoal
     */
    public function setParameter16($parameter16 = null)
    {
        $this->parameter16 = $parameter16;

        return $this;
    }

    /**
     * Get parameter16.
     *
     * @return string|null
     */
    public function getParameter16()
    {
        return $this->parameter16;
    }

    /**
     * Set expectation16.
     *
     * @param string|null $expectation16
     *
     * @return Rolebasedgoal
     */
    public function setExpectation16($expectation16 = null)
    {
        $this->expectation16 = $expectation16;

        return $this;
    }

    /**
     * Get expectation16.
     *
     * @return string|null
     */
    public function getExpectation16()
    {
        return $this->expectation16;
    }

    /**
     * Set maxRating16.
     *
     * @param int|null $maxRating16
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating16($maxRating16 = null)
    {
        $this->maxRating16 = $maxRating16;

        return $this;
    }

    /**
     * Get maxRating16.
     *
     * @return int|null
     */
    public function getMaxRating16()
    {
        return $this->maxRating16;
    }

    /**
     * Set parameter17.
     *
     * @param string|null $parameter17
     *
     * @return Rolebasedgoal
     */
    public function setParameter17($parameter17 = null)
    {
        $this->parameter17 = $parameter17;

        return $this;
    }

    /**
     * Get parameter17.
     *
     * @return string|null
     */
    public function getParameter17()
    {
        return $this->parameter17;
    }

    /**
     * Set expectation17.
     *
     * @param string|null $expectation17
     *
     * @return Rolebasedgoal
     */
    public function setExpectation17($expectation17 = null)
    {
        $this->expectation17 = $expectation17;

        return $this;
    }

    /**
     * Get expectation17.
     *
     * @return string|null
     */
    public function getExpectation17()
    {
        return $this->expectation17;
    }

    /**
     * Set maxRating17.
     *
     * @param int|null $maxRating17
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating17($maxRating17 = null)
    {
        $this->maxRating17 = $maxRating17;

        return $this;
    }

    /**
     * Get maxRating17.
     *
     * @return int|null
     */
    public function getMaxRating17()
    {
        return $this->maxRating17;
    }

    /**
     * Set parameter18.
     *
     * @param string|null $parameter18
     *
     * @return Rolebasedgoal
     */
    public function setParameter18($parameter18 = null)
    {
        $this->parameter18 = $parameter18;

        return $this;
    }

    /**
     * Get parameter18.
     *
     * @return string|null
     */
    public function getParameter18()
    {
        return $this->parameter18;
    }

    /**
     * Set expectation18.
     *
     * @param string|null $expectation18
     *
     * @return Rolebasedgoal
     */
    public function setExpectation18($expectation18 = null)
    {
        $this->expectation18 = $expectation18;

        return $this;
    }

    /**
     * Get expectation18.
     *
     * @return string|null
     */
    public function getExpectation18()
    {
        return $this->expectation18;
    }

    /**
     * Set maxRating18.
     *
     * @param int|null $maxRating18
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating18($maxRating18 = null)
    {
        $this->maxRating18 = $maxRating18;

        return $this;
    }

    /**
     * Get maxRating18.
     *
     * @return int|null
     */
    public function getMaxRating18()
    {
        return $this->maxRating18;
    }

    /**
     * Set parameter19.
     *
     * @param string|null $parameter19
     *
     * @return Rolebasedgoal
     */
    public function setParameter19($parameter19 = null)
    {
        $this->parameter19 = $parameter19;

        return $this;
    }

    /**
     * Get parameter19.
     *
     * @return string|null
     */
    public function getParameter19()
    {
        return $this->parameter19;
    }

    /**
     * Set expectation19.
     *
     * @param string|null $expectation19
     *
     * @return Rolebasedgoal
     */
    public function setExpectation19($expectation19 = null)
    {
        $this->expectation19 = $expectation19;

        return $this;
    }

    /**
     * Get expectation19.
     *
     * @return string|null
     */
    public function getExpectation19()
    {
        return $this->expectation19;
    }

    /**
     * Set maxRating19.
     *
     * @param int|null $maxRating19
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating19($maxRating19 = null)
    {
        $this->maxRating19 = $maxRating19;

        return $this;
    }

    /**
     * Get maxRating19.
     *
     * @return int|null
     */
    public function getMaxRating19()
    {
        return $this->maxRating19;
    }

    /**
     * Set parameter20.
     *
     * @param string|null $parameter20
     *
     * @return Rolebasedgoal
     */
    public function setParameter20($parameter20 = null)
    {
        $this->parameter20 = $parameter20;

        return $this;
    }

    /**
     * Get parameter20.
     *
     * @return string|null
     */
    public function getParameter20()
    {
        return $this->parameter20;
    }

    /**
     * Set expectation20.
     *
     * @param string|null $expectation20
     *
     * @return Rolebasedgoal
     */
    public function setExpectation20($expectation20 = null)
    {
        $this->expectation20 = $expectation20;

        return $this;
    }

    /**
     * Get expectation20.
     *
     * @return string|null
     */
    public function getExpectation20()
    {
        return $this->expectation20;
    }

    /**
     * Set maxRating20.
     *
     * @param int|null $maxRating20
     *
     * @return Rolebasedgoal
     */
    public function setMaxRating20($maxRating20 = null)
    {
        $this->maxRating20 = $maxRating20;

        return $this;
    }

    /**
     * Get maxRating20.
     *
     * @return int|null
     */
    public function getMaxRating20()
    {
        return $this->maxRating20;
    }
}
