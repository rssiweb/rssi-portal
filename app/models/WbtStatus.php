<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * WbtStatus
 *
 * @ORM\Table(name="wbt_status")
 * @ORM\Entity
 */
class WbtStatus
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="wbt_status_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="wassociatenumber", type="text", nullable=true)
     */
    private $wassociatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="courseid", type="text", nullable=true)
     */
    private $courseid;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="f_score", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $fScore;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="noticebody", type="text", nullable=true)
     */
    private $noticebody;



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
     * Set wassociatenumber.
     *
     * @param string|null $wassociatenumber
     *
     * @return WbtStatus
     */
    public function setWassociatenumber($wassociatenumber = null)
    {
        $this->wassociatenumber = $wassociatenumber;

        return $this;
    }

    /**
     * Get wassociatenumber.
     *
     * @return string|null
     */
    public function getWassociatenumber()
    {
        return $this->wassociatenumber;
    }

    /**
     * Set courseid.
     *
     * @param string|null $courseid
     *
     * @return WbtStatus
     */
    public function setCourseid($courseid = null)
    {
        $this->courseid = $courseid;

        return $this;
    }

    /**
     * Get courseid.
     *
     * @return string|null
     */
    public function getCourseid()
    {
        return $this->courseid;
    }

    /**
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return WbtStatus
     */
    public function setTimestamp($timestamp = null)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp.
     *
     * @return \DateTime|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set fScore.
     *
     * @param string|null $fScore
     *
     * @return WbtStatus
     */
    public function setFScore($fScore = null)
    {
        $this->fScore = $fScore;

        return $this;
    }

    /**
     * Get fScore.
     *
     * @return string|null
     */
    public function getFScore()
    {
        return $this->fScore;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return WbtStatus
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
     * Set noticebody.
     *
     * @param string|null $noticebody
     *
     * @return WbtStatus
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
