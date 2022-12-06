<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * QpaperQpaper
 *
 * @ORM\Table(name="qpaper_qpaper")
 * @ORM\Entity
 */
class QpaperQpaper
{
    /**
     * @var int
     *
     * @ORM\Column(name="__hevo_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="qpaper_qpaper___hevo_id_seq", allocationSize=1, initialValue=1)
     */
    private $hevoId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=512, nullable=true)
     */
    private $name;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="qpaper", type="string", length=512, nullable=true)
     */
    private $qpaper;

    /**
     * @var int|null
     *
     * @ORM\Column(name="__hevo__ingested_at", type="bigint", nullable=true)
     */
    private $hevoIngestedAt;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="__hevo__marked_deleted", type="boolean", nullable=true)
     */
    private $hevoMarkedDeleted;

    /**
     * @var string|null
     *
     * @ORM\Column(name="associatenumber", type="string", length=512, nullable=true)
     */
    private $associatenumber;



    /**
     * Get hevoId.
     *
     * @return int
     */
    public function getHevoId()
    {
        return $this->hevoId;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return QpaperQpaper
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
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return QpaperQpaper
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
     * Set qpaper.
     *
     * @param string|null $qpaper
     *
     * @return QpaperQpaper
     */
    public function setQpaper($qpaper = null)
    {
        $this->qpaper = $qpaper;

        return $this;
    }

    /**
     * Get qpaper.
     *
     * @return string|null
     */
    public function getQpaper()
    {
        return $this->qpaper;
    }

    /**
     * Set hevoIngestedAt.
     *
     * @param int|null $hevoIngestedAt
     *
     * @return QpaperQpaper
     */
    public function setHevoIngestedAt($hevoIngestedAt = null)
    {
        $this->hevoIngestedAt = $hevoIngestedAt;

        return $this;
    }

    /**
     * Get hevoIngestedAt.
     *
     * @return int|null
     */
    public function getHevoIngestedAt()
    {
        return $this->hevoIngestedAt;
    }

    /**
     * Set hevoMarkedDeleted.
     *
     * @param bool|null $hevoMarkedDeleted
     *
     * @return QpaperQpaper
     */
    public function setHevoMarkedDeleted($hevoMarkedDeleted = null)
    {
        $this->hevoMarkedDeleted = $hevoMarkedDeleted;

        return $this;
    }

    /**
     * Get hevoMarkedDeleted.
     *
     * @return bool|null
     */
    public function getHevoMarkedDeleted()
    {
        return $this->hevoMarkedDeleted;
    }

    /**
     * Set associatenumber.
     *
     * @param string|null $associatenumber
     *
     * @return QpaperQpaper
     */
    public function setAssociatenumber($associatenumber = null)
    {
        $this->associatenumber = $associatenumber;

        return $this;
    }

    /**
     * Get associatenumber.
     *
     * @return string|null
     */
    public function getAssociatenumber()
    {
        return $this->associatenumber;
    }
}
