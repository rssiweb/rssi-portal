<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * BookdataBook
 *
 * @ORM\Table(name="bookdata_book")
 * @ORM\Entity
 */
class BookdataBook
{
    /**
     * @var string
     *
     * @ORM\Column(name="orderid", type="text", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="bookdata_book_orderid_seq", allocationSize=1, initialValue=1)
     */
    private $orderid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bookregno", type="text", nullable=true)
     */
    private $bookregno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bookname", type="text", nullable=true)
     */
    private $bookname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="yourid", type="text", nullable=true)
     */
    private $yourid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="yourname", type="text", nullable=true)
     */
    private $yourname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="originalprice", type="text", nullable=true)
     */
    private $originalprice;

    /**
     * @var string|null
     *
     * @ORM\Column(name="orderdate", type="text", nullable=true)
     */
    private $orderdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issuedon", type="text", nullable=true)
     */
    private $issuedon;

    /**
     * @var string|null
     *
     * @ORM\Column(name="duedate", type="text", nullable=true)
     */
    private $duedate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="bookstatus", type="text", nullable=true)
     */
    private $bookstatus;

    /**
     * @var string|null
     *
     * @ORM\Column(name="remarks", type="text", nullable=true)
     */
    private $remarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="timestamp", type="text", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="processclose", type="text", nullable=true)
     */
    private $processclose;



    /**
     * Get orderid.
     *
     * @return string
     */
    public function getOrderid()
    {
        return $this->orderid;
    }

    /**
     * Set bookregno.
     *
     * @param string|null $bookregno
     *
     * @return BookdataBook
     */
    public function setBookregno($bookregno = null)
    {
        $this->bookregno = $bookregno;

        return $this;
    }

    /**
     * Get bookregno.
     *
     * @return string|null
     */
    public function getBookregno()
    {
        return $this->bookregno;
    }

    /**
     * Set bookname.
     *
     * @param string|null $bookname
     *
     * @return BookdataBook
     */
    public function setBookname($bookname = null)
    {
        $this->bookname = $bookname;

        return $this;
    }

    /**
     * Get bookname.
     *
     * @return string|null
     */
    public function getBookname()
    {
        return $this->bookname;
    }

    /**
     * Set yourid.
     *
     * @param string|null $yourid
     *
     * @return BookdataBook
     */
    public function setYourid($yourid = null)
    {
        $this->yourid = $yourid;

        return $this;
    }

    /**
     * Get yourid.
     *
     * @return string|null
     */
    public function getYourid()
    {
        return $this->yourid;
    }

    /**
     * Set yourname.
     *
     * @param string|null $yourname
     *
     * @return BookdataBook
     */
    public function setYourname($yourname = null)
    {
        $this->yourname = $yourname;

        return $this;
    }

    /**
     * Get yourname.
     *
     * @return string|null
     */
    public function getYourname()
    {
        return $this->yourname;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return BookdataBook
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
     * Set originalprice.
     *
     * @param string|null $originalprice
     *
     * @return BookdataBook
     */
    public function setOriginalprice($originalprice = null)
    {
        $this->originalprice = $originalprice;

        return $this;
    }

    /**
     * Get originalprice.
     *
     * @return string|null
     */
    public function getOriginalprice()
    {
        return $this->originalprice;
    }

    /**
     * Set orderdate.
     *
     * @param string|null $orderdate
     *
     * @return BookdataBook
     */
    public function setOrderdate($orderdate = null)
    {
        $this->orderdate = $orderdate;

        return $this;
    }

    /**
     * Get orderdate.
     *
     * @return string|null
     */
    public function getOrderdate()
    {
        return $this->orderdate;
    }

    /**
     * Set issuedon.
     *
     * @param string|null $issuedon
     *
     * @return BookdataBook
     */
    public function setIssuedon($issuedon = null)
    {
        $this->issuedon = $issuedon;

        return $this;
    }

    /**
     * Get issuedon.
     *
     * @return string|null
     */
    public function getIssuedon()
    {
        return $this->issuedon;
    }

    /**
     * Set duedate.
     *
     * @param string|null $duedate
     *
     * @return BookdataBook
     */
    public function setDuedate($duedate = null)
    {
        $this->duedate = $duedate;

        return $this;
    }

    /**
     * Get duedate.
     *
     * @return string|null
     */
    public function getDuedate()
    {
        return $this->duedate;
    }

    /**
     * Set bookstatus.
     *
     * @param string|null $bookstatus
     *
     * @return BookdataBook
     */
    public function setBookstatus($bookstatus = null)
    {
        $this->bookstatus = $bookstatus;

        return $this;
    }

    /**
     * Get bookstatus.
     *
     * @return string|null
     */
    public function getBookstatus()
    {
        return $this->bookstatus;
    }

    /**
     * Set remarks.
     *
     * @param string|null $remarks
     *
     * @return BookdataBook
     */
    public function setRemarks($remarks = null)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks.
     *
     * @return string|null
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set timestamp.
     *
     * @param string|null $timestamp
     *
     * @return BookdataBook
     */
    public function setTimestamp($timestamp = null)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp.
     *
     * @return string|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set processclose.
     *
     * @param string|null $processclose
     *
     * @return BookdataBook
     */
    public function setProcessclose($processclose = null)
    {
        $this->processclose = $processclose;

        return $this;
    }

    /**
     * Get processclose.
     *
     * @return string|null
     */
    public function getProcessclose()
    {
        return $this->processclose;
    }
}
