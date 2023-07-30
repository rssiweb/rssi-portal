<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Attendance
 *
 * @ORM\Table(name="attendance")
 * @ORM\Entity
 */
class Attendance
{
    /**
     * @var int
     *
     * @ORM\Column(name="sl_no", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="attendance_sl_no_seq", allocationSize=1, initialValue=1)
     */
    private $slNo;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=50, nullable=false)
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="punch_in", type="datetime", nullable=false)
     */
    private $punchIn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_address", type="string", length=50, nullable=true)
     */
    private $ipAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gps_location", type="string", length=100, nullable=true)
     */
    private $gpsLocation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="recorded_by", type="string", length=50, nullable=true)
     */
    private $recordedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;



    /**
     * Get slNo.
     *
     * @return int
     */
    public function getSlNo()
    {
        return $this->slNo;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     *
     * @return Attendance
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set punchIn.
     *
     * @param \DateTime $punchIn
     *
     * @return Attendance
     */
    public function setPunchIn($punchIn)
    {
        $this->punchIn = $punchIn;

        return $this;
    }

    /**
     * Get punchIn.
     *
     * @return \DateTime
     */
    public function getPunchIn()
    {
        return $this->punchIn;
    }

    /**
     * Set ipAddress.
     *
     * @param string|null $ipAddress
     *
     * @return Attendance
     */
    public function setIpAddress($ipAddress = null)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set gpsLocation.
     *
     * @param string|null $gpsLocation
     *
     * @return Attendance
     */
    public function setGpsLocation($gpsLocation = null)
    {
        $this->gpsLocation = $gpsLocation;

        return $this;
    }

    /**
     * Get gpsLocation.
     *
     * @return string|null
     */
    public function getGpsLocation()
    {
        return $this->gpsLocation;
    }

    /**
     * Set recordedBy.
     *
     * @param string|null $recordedBy
     *
     * @return Attendance
     */
    public function setRecordedBy($recordedBy = null)
    {
        $this->recordedBy = $recordedBy;

        return $this;
    }

    /**
     * Get recordedBy.
     *
     * @return string|null
     */
    public function getRecordedBy()
    {
        return $this->recordedBy;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return Attendance
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
