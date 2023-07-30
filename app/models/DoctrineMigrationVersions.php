<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * DoctrineMigrationVersions
 *
 * @ORM\Table(name="doctrine_migration_versions")
 * @ORM\Entity
 */
class DoctrineMigrationVersions
{
    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=191, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="doctrine_migration_versions_version_seq", allocationSize=1, initialValue=1)
     */
    private $version;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="executed_at", type="datetime", nullable=true)
     */
    private $executedAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="execution_time", type="integer", nullable=true)
     */
    private $executionTime;



    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set executedAt.
     *
     * @param \DateTime|null $executedAt
     *
     * @return DoctrineMigrationVersions
     */
    public function setExecutedAt($executedAt = null)
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    /**
     * Get executedAt.
     *
     * @return \DateTime|null
     */
    public function getExecutedAt()
    {
        return $this->executedAt;
    }

    /**
     * Set executionTime.
     *
     * @param int|null $executionTime
     *
     * @return DoctrineMigrationVersions
     */
    public function setExecutionTime($executionTime = null)
    {
        $this->executionTime = $executionTime;

        return $this;
    }

    /**
     * Get executionTime.
     *
     * @return int|null
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }
}
