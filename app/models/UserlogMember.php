<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * UserlogMember
 *
 * @ORM\Table(name="userlog_member")
 * @ORM\Entity
 */
class UserlogMember
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="userlog_member_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="username", type="text", nullable=true)
     */
    private $username;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipaddress", type="text", nullable=true)
     */
    private $ipaddress;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="logintime", type="datetime", nullable=true)
     */
    private $logintime;



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
     * Set username.
     *
     * @param string|null $username
     *
     * @return UserlogMember
     */
    public function setUsername($username = null)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set ipaddress.
     *
     * @param string|null $ipaddress
     *
     * @return UserlogMember
     */
    public function setIpaddress($ipaddress = null)
    {
        $this->ipaddress = $ipaddress;

        return $this;
    }

    /**
     * Get ipaddress.
     *
     * @return string|null
     */
    public function getIpaddress()
    {
        return $this->ipaddress;
    }

    /**
     * Set logintime.
     *
     * @param \DateTime|null $logintime
     *
     * @return UserlogMember
     */
    public function setLogintime($logintime = null)
    {
        $this->logintime = $logintime;

        return $this;
    }

    /**
     * Get logintime.
     *
     * @return \DateTime|null
     */
    public function getLogintime()
    {
        return $this->logintime;
    }
}
