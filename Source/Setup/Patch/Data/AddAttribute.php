<?php
/**
 * Greetings from Wamoco GmbH, Bremen, Germany.
 * @author Wamoco Team<info@wamoco.de>
 * @license See LICENSE.txt for license details.
 */

namespace Wamoco\ForcePasswordReset\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Customer;

/**
 * Class: AddAttribute
 *
 * @see DataPatchInterface
 */
class AddAttribute implements DataPatchInterface
{
    const ATTRIBUTE_CODE = "password_reset_required";

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $config;

    /**
     * __construct
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $setup
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     * @param \Magento\Catalog\Model\Config $config
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $setup,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Catalog\Model\Config $config
    ) {
        $this->eavSetupFactory  = $eavSetupFactory;
        $this->attributeFactory = $attributeFactory;
        $this->setup            = $setup;
        $this->config           = $config;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        $this->setup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->addAttribute(
            Customer::ENTITY,
            self::ATTRIBUTE_CODE,
            [
                'type'           => 'int',
                'label'          => 'Is Password Reset Required',
                'input'          => 'boolean',
                'backend'        => \Magento\Customer\Model\Attribute\Backend\Data\Boolean::class,
                'position'       => 125,
                'required'       => false,
                'system'         => false, // this is important, that is is not a system attribute
                'adminhtml_only' => true,
            ]

        );

        $attributeId = $this->attributeFactory->create()->loadByCode(Customer::ENTITY, self::ATTRIBUTE_CODE)->getAttributeId();
        if (!$attributeId) {
            throw new \Exception("Attribute " . self::ATTRIBUTE_CODE . " does not exist");
        }

        // NOTE: it is important to assign the attribute to the form group
        $data = [];
        $data[] = ['form_code' => 'adminhtml_customer', 'attribute_id' => $attributeId];

        $this->setup->getConnection()
            ->insertMultiple($this->setup->getTable('customer_form_attribute'), $data);

        $this->setup->getConnection()->endSetup();
    }
}

