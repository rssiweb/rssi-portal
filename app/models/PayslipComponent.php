<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * PayslipComponent
 *
 * @ORM\Table(name="payslip_component", indexes={@ORM\Index(name="IDX_E42A77769682E28", columns={"payslip_entry_id"})})
 * @ORM\Entity
 */
class PayslipComponent
{
    /**
     * @var int
     *
     * @ORM\Column(name="component_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="payslip_component_component_id_seq", allocationSize=1, initialValue=1)
     */
    private $componentId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="components", type="string", length=255, nullable=true)
     */
    private $components;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subcategory", type="string", length=255, nullable=true)
     */
    private $subcategory;

    /**
     * @var string|null
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var \PayslipEntry
     *
     * @ORM\ManyToOne(targetEntity="PayslipEntry")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="payslip_entry_id", referencedColumnName="payslip_entry_id")
     * })
     */
    private $payslipEntry;



    /**
     * Get componentId.
     *
     * @return int
     */
    public function getComponentId()
    {
        return $this->componentId;
    }

    /**
     * Set components.
     *
     * @param string|null $components
     *
     * @return PayslipComponent
     */
    public function setComponents($components = null)
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Get components.
     *
     * @return string|null
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * Set subcategory.
     *
     * @param string|null $subcategory
     *
     * @return PayslipComponent
     */
    public function setSubcategory($subcategory = null)
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    /**
     * Get subcategory.
     *
     * @return string|null
     */
    public function getSubcategory()
    {
        return $this->subcategory;
    }

    /**
     * Set amount.
     *
     * @param string|null $amount
     *
     * @return PayslipComponent
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
     * Set payslipEntry.
     *
     * @param \PayslipEntry|null $payslipEntry
     *
     * @return PayslipComponent
     */
    public function setPayslipEntry(\PayslipEntry $payslipEntry = null)
    {
        $this->payslipEntry = $payslipEntry;

        return $this;
    }

    /**
     * Get payslipEntry.
     *
     * @return \PayslipEntry|null
     */
    public function getPayslipEntry()
    {
        return $this->payslipEntry;
    }
}
