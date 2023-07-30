<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * DonationUserdata
 *
 * @ORM\Table(name="donation_userdata")
 * @ORM\Entity
 */
class DonationUserdata
{
    /**
     * @var string
     *
     * @ORM\Column(name="tel", type="string", length=10, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="donation_userdata_tel_seq", allocationSize=1, initialValue=1)
     */
    private $tel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullname", type="string", length=100, nullable=true)
     */
    private $fullname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="documenttype", type="string", length=50, nullable=true)
     */
    private $documenttype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nationalid", type="string", length=50, nullable=true)
     */
    private $nationalid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postaladdress", type="string", length=200, nullable=true)
     */
    private $postaladdress;



    /**
     * Get tel.
     *
     * @return string
     */
    public function getTel()
    {
        return $this->tel;
    }

    /**
     * Set fullname.
     *
     * @param string|null $fullname
     *
     * @return DonationUserdata
     */
    public function setFullname($fullname = null)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string|null
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return DonationUserdata
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
     * Set documenttype.
     *
     * @param string|null $documenttype
     *
     * @return DonationUserdata
     */
    public function setDocumenttype($documenttype = null)
    {
        $this->documenttype = $documenttype;

        return $this;
    }

    /**
     * Get documenttype.
     *
     * @return string|null
     */
    public function getDocumenttype()
    {
        return $this->documenttype;
    }

    /**
     * Set nationalid.
     *
     * @param string|null $nationalid
     *
     * @return DonationUserdata
     */
    public function setNationalid($nationalid = null)
    {
        $this->nationalid = $nationalid;

        return $this;
    }

    /**
     * Get nationalid.
     *
     * @return string|null
     */
    public function getNationalid()
    {
        return $this->nationalid;
    }

    /**
     * Set postaladdress.
     *
     * @param string|null $postaladdress
     *
     * @return DonationUserdata
     */
    public function setPostaladdress($postaladdress = null)
    {
        $this->postaladdress = $postaladdress;

        return $this;
    }

    /**
     * Get postaladdress.
     *
     * @return string|null
     */
    public function getPostaladdress()
    {
        return $this->postaladdress;
    }
}
