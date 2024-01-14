<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * StockItem
 *
 * @ORM\Table(name="stock_item")
 * @ORM\Entity
 */
class StockItem
{
    /**
     * @var string
     *
     * @ORM\Column(name="item_code", type="string", length=10, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="stock_item_item_code_seq", allocationSize=1, initialValue=1)
     */
    private $itemCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="item_name", type="string", length=50, nullable=true)
     */
    private $itemName;



    /**
     * Get itemCode.
     *
     * @return string
     */
    public function getItemCode()
    {
        return $this->itemCode;
    }

    /**
     * Set itemName.
     *
     * @param string|null $itemName
     *
     * @return StockItem
     */
    public function setItemName($itemName = null)
    {
        $this->itemName = $itemName;

        return $this;
    }

    /**
     * Get itemName.
     *
     * @return string|null
     */
    public function getItemName()
    {
        return $this->itemName;
    }
}
