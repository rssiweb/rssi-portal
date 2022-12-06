<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Test
 *
 * @ORM\Table(name="test")
 * @ORM\Entity
 */
class Test
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="test_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sname", type="text", nullable=true)
     */
    private $sname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sid", type="text", nullable=true)
     */
    private $sid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $amount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="orderid", type="text", nullable=true)
     */
    private $orderid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="orderstatus", type="text", nullable=true)
     */
    private $orderstatus;



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
     * Set date.
     *
     * @param \DateTime|null $date
     *
     * @return Test
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
     * Set sname.
     *
     * @param string|null $sname
     *
     * @return Test
     */
    public function setSname($sname = null)
    {
        $this->sname = $sname;

        return $this;
    }

    /**
     * Get sname.
     *
     * @return string|null
     */
    public function getSname()
    {
        return $this->sname;
    }

    /**
     * Set sid.
     *
     * @param string|null $sid
     *
     * @return Test
     */
    public function setSid($sid = null)
    {
        $this->sid = $sid;

        return $this;
    }

    /**
     * Get sid.
     *
     * @return string|null
     */
    public function getSid()
    {
        return $this->sid;
    }

    /**
     * Set amount.
     *
     * @param string|null $amount
     *
     * @return Test
     */
    public function setAmount($amount = null)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return string|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set orderid.
     *
     * @param string|null $orderid
     *
     * @return Test
     */
    public function setOrderid($orderid = null)
    {
        $this->orderid = $orderid;

        return $this;
    }

    /**
     * Get orderid.
     *
     * @return string|null
     */
    public function getOrderid()
    {
        return $this->orderid;
    }

    /**
     * Set orderstatus.
     *
     * @param string|null $orderstatus
     *
     * @return Test
     */
    public function setOrderstatus($orderstatus = null)
    {
        $this->orderstatus = $orderstatus;

        return $this;
    }

    /**
     * Get orderstatus.
     *
     * @return string|null
     */
    public function getOrderstatus()
    {
        return $this->orderstatus;
    }
}
