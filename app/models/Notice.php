<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Notice
 *
 * @ORM\Table(name="notice")
 * @ORM\Entity
 */
class Notice
{
    /**
     * @var string
     *
     * @ORM\Column(name="noticeid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="notice_noticeid_seq", allocationSize=1, initialValue=1)
     */
    private $noticeid;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="text", nullable=true)
     */
    private $subject;

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
     * @ORM\Column(name="category", type="text", nullable=true)
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="noticebody", type="text", nullable=true)
     */
    private $noticebody;



    /**
     * Get noticeid.
     *
     * @return string
     */
    public function getNoticeid()
    {
        return $this->noticeid;
    }

    /**
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return Notice
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
     * Set subject.
     *
     * @param string|null $subject
     *
     * @return Notice
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
     * Set url.
     *
     * @param string|null $url
     *
     * @return Notice
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
     * @return Notice
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
     * Set category.
     *
     * @param string|null $category
     *
     * @return Notice
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
     * Set noticebody.
     *
     * @param string|null $noticebody
     *
     * @return Notice
     */
    public function setNoticebody($noticebody = null)
    {
        $this->noticebody = $noticebody;

        return $this;
    }

    /**
     * Get noticebody.
     *
     * @return string|null
     */
    public function getNoticebody()
    {
        return $this->noticebody;
    }
}
