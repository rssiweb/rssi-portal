<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Ipfsubmission
 *
 * @ORM\Table(name="ipfsubmission")
 * @ORM\Entity
 */
class Ipfsubmission
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="ipfsubmission_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="memberid2", type="text", nullable=true)
     */
    private $memberid2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="membername2", type="text", nullable=true)
     */
    private $membername2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf", type="text", nullable=true)
     */
    private $ipf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipfinitiate", type="text", nullable=true)
     */
    private $ipfinitiate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status2", type="text", nullable=true)
     */
    private $status2;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="respondedon", type="datetime", nullable=true)
     */
    private $respondedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipfstatus", type="text", nullable=true)
     */
    private $ipfstatus;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="closedon", type="datetime", nullable=true)
     */
    private $closedon;



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
     * Set timestamp.
     *
     * @param \DateTime|null $timestamp
     *
     * @return Ipfsubmission
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
     * Set memberid2.
     *
     * @param string|null $memberid2
     *
     * @return Ipfsubmission
     */
    public function setMemberid2($memberid2 = null)
    {
        $this->memberid2 = $memberid2;

        return $this;
    }

    /**
     * Get memberid2.
     *
     * @return string|null
     */
    public function getMemberid2()
    {
        return $this->memberid2;
    }

    /**
     * Set membername2.
     *
     * @param string|null $membername2
     *
     * @return Ipfsubmission
     */
    public function setMembername2($membername2 = null)
    {
        $this->membername2 = $membername2;

        return $this;
    }

    /**
     * Get membername2.
     *
     * @return string|null
     */
    public function getMembername2()
    {
        return $this->membername2;
    }

    /**
     * Set ipf.
     *
     * @param string|null $ipf
     *
     * @return Ipfsubmission
     */
    public function setIpf($ipf = null)
    {
        $this->ipf = $ipf;

        return $this;
    }

    /**
     * Get ipf.
     *
     * @return string|null
     */
    public function getIpf()
    {
        return $this->ipf;
    }

    /**
     * Set ipfinitiate.
     *
     * @param string|null $ipfinitiate
     *
     * @return Ipfsubmission
     */
    public function setIpfinitiate($ipfinitiate = null)
    {
        $this->ipfinitiate = $ipfinitiate;

        return $this;
    }

    /**
     * Get ipfinitiate.
     *
     * @return string|null
     */
    public function getIpfinitiate()
    {
        return $this->ipfinitiate;
    }

    /**
     * Set status2.
     *
     * @param string|null $status2
     *
     * @return Ipfsubmission
     */
    public function setStatus2($status2 = null)
    {
        $this->status2 = $status2;

        return $this;
    }

    /**
     * Get status2.
     *
     * @return string|null
     */
    public function getStatus2()
    {
        return $this->status2;
    }

    /**
     * Set respondedon.
     *
     * @param \DateTime|null $respondedon
     *
     * @return Ipfsubmission
     */
    public function setRespondedon($respondedon = null)
    {
        $this->respondedon = $respondedon;

        return $this;
    }

    /**
     * Get respondedon.
     *
     * @return \DateTime|null
     */
    public function getRespondedon()
    {
        return $this->respondedon;
    }

    /**
     * Set ipfstatus.
     *
     * @param string|null $ipfstatus
     *
     * @return Ipfsubmission
     */
    public function setIpfstatus($ipfstatus = null)
    {
        $this->ipfstatus = $ipfstatus;

        return $this;
    }

    /**
     * Get ipfstatus.
     *
     * @return string|null
     */
    public function getIpfstatus()
    {
        return $this->ipfstatus;
    }

    /**
     * Set closedon.
     *
     * @param \DateTime|null $closedon
     *
     * @return Ipfsubmission
     */
    public function setClosedon($closedon = null)
    {
        $this->closedon = $closedon;

        return $this;
    }

    /**
     * Get closedon.
     *
     * @return \DateTime|null
     */
    public function getClosedon()
    {
        return $this->closedon;
    }
}
