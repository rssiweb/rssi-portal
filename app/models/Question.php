<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Question
 *
 * @ORM\Table(name="question")
 * @ORM\Entity
 */
class Question
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="question_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="examname", type="text", nullable=true)
     */
    private $examname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="text", nullable=true)
     */
    private $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="topic", type="text", nullable=true)
     */
    private $topic;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullmarks", type="text", nullable=true)
     */
    private $fullmarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="year", type="text", nullable=true)
     */
    private $year;

    /**
     * @var string|null
     *
     * @ORM\Column(name="testcode", type="text", nullable=true)
     */
    private $testcode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="text", nullable=true)
     */
    private $url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="class", type="text", nullable=true)
     */
    private $class;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="flag", type="datetime", nullable=true)
     */
    private $flag;



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
     * Set category.
     *
     * @param string|null $category
     *
     * @return Question
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
     * Set examname.
     *
     * @param string|null $examname
     *
     * @return Question
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
     * Set subject.
     *
     * @param string|null $subject
     *
     * @return Question
     */
    public function setSubject($subject = null)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set topic.
     *
     * @param string|null $topic
     *
     * @return Question
     */
    public function setTopic($topic = null)
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get topic.
     *
     * @return string|null
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Set fullmarks.
     *
     * @param string|null $fullmarks
     *
     * @return Question
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
     * Set year.
     *
     * @param string|null $year
     *
     * @return Question
     */
    public function setYear($year = null)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year.
     *
     * @return string|null
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set testcode.
     *
     * @param string|null $testcode
     *
     * @return Question
     */
    public function setTestcode($testcode = null)
    {
        $this->testcode = $testcode;

        return $this;
    }

    /**
     * Get testcode.
     *
     * @return string|null
     */
    public function getTestcode()
    {
        return $this->testcode;
    }

    /**
     * Set url.
     *
     * @param string|null $url
     *
     * @return Question
     */
    public function setUrl($url = null)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set class.
     *
     * @param string|null $class
     *
     * @return Question
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
     * Set flag.
     *
     * @param \DateTime|null $flag
     *
     * @return Question
     */
    public function setFlag($flag = null)
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * Get flag.
     *
     * @return \DateTime|null
     */
    public function getFlag()
    {
        return $this->flag;
    }
}
