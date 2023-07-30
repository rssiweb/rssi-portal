<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * IpfHistory
 *
 * @ORM\Table(name="ipf_history")
 * @ORM\Entity
 */
class IpfHistory
{
    /**
     * @var string
     *
     * @ORM\Column(name="goalsheetid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="ipf_history_goalsheetid_seq", allocationSize=1, initialValue=1)
     */
    private $goalsheetid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $ipf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf_response", type="text", nullable=true)
     */
    private $ipfResponse;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf_response_by", type="text", nullable=true)
     */
    private $ipfResponseBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="ipf_response_on", type="datetime", nullable=true)
     */
    private $ipfResponseOn;



    /**
     * Get goalsheetid.
     *
     * @return string
     */
    public function getGoalsheetid()
    {
        return $this->goalsheetid;
    }

    /**
     * Set ipf.
     *
     * @param string|null $ipf
     *
     * @return IpfHistory
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
     * Set ipfResponse.
     *
     * @param string|null $ipfResponse
     *
     * @return IpfHistory
     */
    public function setIpfResponse($ipfResponse = null)
    {
        $this->ipfResponse = $ipfResponse;

        return $this;
    }

    /**
     * Get ipfResponse.
     *
     * @return string|null
     */
    public function getIpfResponse()
    {
        return $this->ipfResponse;
    }

    /**
     * Set ipfResponseBy.
     *
     * @param string|null $ipfResponseBy
     *
     * @return IpfHistory
     */
    public function setIpfResponseBy($ipfResponseBy = null)
    {
        $this->ipfResponseBy = $ipfResponseBy;

        return $this;
    }

    /**
     * Get ipfResponseBy.
     *
     * @return string|null
     */
    public function getIpfResponseBy()
    {
        return $this->ipfResponseBy;
    }

    /**
     * Set ipfResponseOn.
     *
     * @param \DateTime|null $ipfResponseOn
     *
     * @return IpfHistory
     */
    public function setIpfResponseOn($ipfResponseOn = null)
    {
        $this->ipfResponseOn = $ipfResponseOn;

        return $this;
    }

    /**
     * Get ipfResponseOn.
     *
     * @return \DateTime|null
     */
    public function getIpfResponseOn()
    {
        return $this->ipfResponseOn;
    }
}
