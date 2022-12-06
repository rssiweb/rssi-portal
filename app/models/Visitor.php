<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Visitor
 *
 * @ORM\Table(name="visitor")
 * @ORM\Entity
 */
class Visitor
{
    /**
     * @var int
     *
     * @ORM\Column(name="slno", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="visitor_slno_seq", allocationSize=1, initialValue=1)
     */
    private $slno;

    /**
     * @var string|null
     *
     * @ORM\Column(name="timestamp", type="text", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="visitorname", type="text", nullable=true)
     */
    private $visitorname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="purposeofvisit", type="text", nullable=true)
     */
    private $purposeofvisit;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="visitdatefrom", type="datetime", nullable=true)
     */
    private $visitdatefrom;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="visitdateto", type="datetime", nullable=true)
     */
    private $visitdateto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="aadharcard", type="text", nullable=true)
     */
    private $aadharcard;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photo", type="text", nullable=true)
     */
    private $photo;

    /**
     * @var string|null
     *
     * @ORM\Column(name="visitorid", type="text", nullable=true)
     */
    private $visitorid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="text", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="authority", type="text", nullable=true)
     */
    private $authority;

    /**
     * @var string|null
     *
     * @ORM\Column(name="visited", type="text", nullable=true)
     */
    private $visited;

    /**
     * @var string|null
     *
     * @ORM\Column(name="existingid", type="text", nullable=true)
     */
    private $existingid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contact", type="text", nullable=true)
     */
    private $contact;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="branch", type="text", nullable=true)
     */
    private $branch;

    /**
     * @var string|null
     *
     * @ORM\Column(name="visittime", type="text", nullable=true)
     */
    private $visittime;

    /**
     * @var string|null
     *
     * @ORM\Column(name="raw_visitorname", type="text", nullable=true)
     */
    private $rawVisitorname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="raw_aadharcard", type="text", nullable=true)
     */
    private $rawAadharcard;

    /**
     * @var string|null
     *
     * @ORM\Column(name="raw_photo", type="text", nullable=true)
     */
    private $rawPhoto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="raw_contact", type="text", nullable=true)
     */
    private $rawContact;



    /**
     * Get slno.
     *
     * @return int
     */
    public function getSlno()
    {
        return $this->slno;
    }

    /**
     * Set timestamp.
     *
     * @param string|null $timestamp
     *
     * @return Visitor
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
     * Set visitorname.
     *
     * @param string|null $visitorname
     *
     * @return Visitor
     */
    public function setVisitorname($visitorname = null)
    {
        $this->visitorname = $visitorname;

        return $this;
    }

    /**
     * Get visitorname.
     *
     * @return string|null
     */
    public function getVisitorname()
    {
        return $this->visitorname;
    }

    /**
     * Set purposeofvisit.
     *
     * @param string|null $purposeofvisit
     *
     * @return Visitor
     */
    public function setPurposeofvisit($purposeofvisit = null)
    {
        $this->purposeofvisit = $purposeofvisit;

        return $this;
    }

    /**
     * Get purposeofvisit.
     *
     * @return string|null
     */
    public function getPurposeofvisit()
    {
        return $this->purposeofvisit;
    }

    /**
     * Set visitdatefrom.
     *
     * @param \DateTime|null $visitdatefrom
     *
     * @return Visitor
     */
    public function setVisitdatefrom($visitdatefrom = null)
    {
        $this->visitdatefrom = $visitdatefrom;

        return $this;
    }

    /**
     * Get visitdatefrom.
     *
     * @return \DateTime|null
     */
    public function getVisitdatefrom()
    {
        return $this->visitdatefrom;
    }

    /**
     * Set visitdateto.
     *
     * @param \DateTime|null $visitdateto
     *
     * @return Visitor
     */
    public function setVisitdateto($visitdateto = null)
    {
        $this->visitdateto = $visitdateto;

        return $this;
    }

    /**
     * Get visitdateto.
     *
     * @return \DateTime|null
     */
    public function getVisitdateto()
    {
        return $this->visitdateto;
    }

    /**
     * Set aadharcard.
     *
     * @param string|null $aadharcard
     *
     * @return Visitor
     */
    public function setAadharcard($aadharcard = null)
    {
        $this->aadharcard = $aadharcard;

        return $this;
    }

    /**
     * Get aadharcard.
     *
     * @return string|null
     */
    public function getAadharcard()
    {
        return $this->aadharcard;
    }

    /**
     * Set photo.
     *
     * @param string|null $photo
     *
     * @return Visitor
     */
    public function setPhoto($photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo.
     *
     * @return string|null
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set visitorid.
     *
     * @param string|null $visitorid
     *
     * @return Visitor
     */
    public function setVisitorid($visitorid = null)
    {
        $this->visitorid = $visitorid;

        return $this;
    }

    /**
     * Get visitorid.
     *
     * @return string|null
     */
    public function getVisitorid()
    {
        return $this->visitorid;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return Visitor
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set authority.
     *
     * @param string|null $authority
     *
     * @return Visitor
     */
    public function setAuthority($authority = null)
    {
        $this->authority = $authority;

        return $this;
    }

    /**
     * Get authority.
     *
     * @return string|null
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * Set visited.
     *
     * @param string|null $visited
     *
     * @return Visitor
     */
    public function setVisited($visited = null)
    {
        $this->visited = $visited;

        return $this;
    }

    /**
     * Get visited.
     *
     * @return string|null
     */
    public function getVisited()
    {
        return $this->visited;
    }

    /**
     * Set existingid.
     *
     * @param string|null $existingid
     *
     * @return Visitor
     */
    public function setExistingid($existingid = null)
    {
        $this->existingid = $existingid;

        return $this;
    }

    /**
     * Get existingid.
     *
     * @return string|null
     */
    public function getExistingid()
    {
        return $this->existingid;
    }

    /**
     * Set contact.
     *
     * @param string|null $contact
     *
     * @return Visitor
     */
    public function setContact($contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return string|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return Visitor
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
     * Set branch.
     *
     * @param string|null $branch
     *
     * @return Visitor
     */
    public function setBranch($branch = null)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * Get branch.
     *
     * @return string|null
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * Set visittime.
     *
     * @param string|null $visittime
     *
     * @return Visitor
     */
    public function setVisittime($visittime = null)
    {
        $this->visittime = $visittime;

        return $this;
    }

    /**
     * Get visittime.
     *
     * @return string|null
     */
    public function getVisittime()
    {
        return $this->visittime;
    }

    /**
     * Set rawVisitorname.
     *
     * @param string|null $rawVisitorname
     *
     * @return Visitor
     */
    public function setRawVisitorname($rawVisitorname = null)
    {
        $this->rawVisitorname = $rawVisitorname;

        return $this;
    }

    /**
     * Get rawVisitorname.
     *
     * @return string|null
     */
    public function getRawVisitorname()
    {
        return $this->rawVisitorname;
    }

    /**
     * Set rawAadharcard.
     *
     * @param string|null $rawAadharcard
     *
     * @return Visitor
     */
    public function setRawAadharcard($rawAadharcard = null)
    {
        $this->rawAadharcard = $rawAadharcard;

        return $this;
    }

    /**
     * Get rawAadharcard.
     *
     * @return string|null
     */
    public function getRawAadharcard()
    {
        return $this->rawAadharcard;
    }

    /**
     * Set rawPhoto.
     *
     * @param string|null $rawPhoto
     *
     * @return Visitor
     */
    public function setRawPhoto($rawPhoto = null)
    {
        $this->rawPhoto = $rawPhoto;

        return $this;
    }

    /**
     * Get rawPhoto.
     *
     * @return string|null
     */
    public function getRawPhoto()
    {
        return $this->rawPhoto;
    }

    /**
     * Set rawContact.
     *
     * @param string|null $rawContact
     *
     * @return Visitor
     */
    public function setRawContact($rawContact = null)
    {
        $this->rawContact = $rawContact;

        return $this;
    }

    /**
     * Get rawContact.
     *
     * @return string|null
     */
    public function getRawContact()
    {
        return $this->rawContact;
    }
}
