<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Wbt
 *
 * @ORM\Table(name="wbt")
 * @ORM\Entity
 */
class Wbt
{
    /**
     * @var string
     *
     * @ORM\Column(name="courseid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="wbt_courseid_seq", allocationSize=1, initialValue=1)
     */
    private $courseid;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="coursename", type="text", nullable=true)
     */
    private $coursename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="language", type="text", nullable=true)
     */
    private $language;

    /**
     * @var string|null
     *
     * @ORM\Column(name="passingmarks", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $passingmarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="text", nullable=true)
     */
    private $url;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issuedby", type="text", nullable=true)
     */
    private $issuedby;

    /**
     * @var string|null
     *
     * @ORM\Column(name="validity", type="text", nullable=true)
     */
    private $validity;



    /**
     * Get courseid.
     *
     * @return string
     */
    public function getCourseid()
    {
        return $this->courseid;
    }

    /**
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return Wbt
     */
    public function setDate($date = null)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set coursename.
     *
     * @param string|null $coursename
     *
     * @return Wbt
     */
    public function setCoursename($coursename = null)
    {
        $this->coursename = $coursename;

        return $this;
    }

    /**
     * Get coursename.
     *
     * @return string|null
     */
    public function getCoursename()
    {
        return $this->coursename;
    }

    /**
     * Set language.
     *
     * @param string|null $language
     *
     * @return Wbt
     */
    public function setLanguage($language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set passingmarks.
     *
     * @param string|null $passingmarks
     *
     * @return Wbt
     */
    public function setPassingmarks($passingmarks = null)
    {
        $this->passingmarks = $passingmarks;

        return $this;
    }

    /**
     * Get passingmarks.
     *
     * @return string|null
     */
    public function getPassingmarks()
    {
        return $this->passingmarks;
    }

    /**
     * Set url.
     *
     * @param string|null $url
     *
     * @return Wbt
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
     * Set issuedby.
     *
     * @param string|null $issuedby
     *
     * @return Wbt
     */
    public function setIssuedby($issuedby = null)
    {
        $this->issuedby = $issuedby;

        return $this;
    }

    /**
     * Get issuedby.
     *
     * @return string|null
     */
    public function getIssuedby()
    {
        return $this->issuedby;
    }

    /**
     * Set validity.
     *
     * @param string|null $validity
     *
     * @return Wbt
     */
    public function setValidity($validity = null)
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get validity.
     *
     * @return string|null
     */
    public function getValidity()
    {
        return $this->validity;
    }
}
