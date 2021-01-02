<?php
/**
 * Greetings from Wamoco GmbH, Bremen, Germany.
 * @author Wamoco Team<info@wamoco.de>
 * @license See LICENSE.txt for license details.
 */

namespace Wamoco\ForcePasswordReset\Plugin\Customer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Wamoco\ForcePasswordReset\Model\Config;

/**
 * Class: AccountManagement
 */
class AccountManagement
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GetCustomerByToken
     */
    protected $getByToken;

    /**
     * __construct
     *
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param GetCustomerByToken $getByToken
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        GetCustomerByToken $getByToken
    ) {
        $this->config          = $config;
        $this->storeManager    = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->getByToken      = $getByToken;
    }

    /**
     * beforeAuthenticate
     *
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param mixed $email
     * @param mixed $password
     */
    public function beforeAuthenticate(
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        $email,
        $password
    ) {
        $customer = $this->loadCustomerByEmail($email);

        if ($customer && $customer->getId() && $customer->getPasswordResetRequired()) {
            $accountManagement->initiatePasswordReset(
                $email,
                $this->config->getTemplate(),
                $customer->getWebsiteId()
            );
            throw new LocalizedException($this->config->getMessage());
        }

        return [$email, $password];
    }

    /**
     * aroundResetPassword
     *
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param callable $proceed
     * @param mixed $email
     * @param mixed $resetToken
     * @param mixed $newPassword
     */
    public function aroundResetPassword(
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        callable $proceed,
        $email,
        $resetToken,
        $newPassword
    ) {
        // note: customer needs to be loaded first, reset token expires after proceed
        $customer = (!$email) ? $this->loadCustomerByToken($resetToken) : $this->loadCustomerByEmail($email);

        $isSuccess = $proceed($email, $resetToken, $newPassword);
        if ($isSuccess && $customer && $customer->getId() && $customer->getPasswordResetRequired()) {
            $customer->setPasswordResetRequired(false);
            $customer->save();
        }
        return $isSuccess;
    }

    /**
     * loadCustomerByToken
     *
     * @param string $token
     * @return \Magento\Customer\Model\Customer|null
     */
    protected function loadCustomerByToken($token)
    {
        $customerData = $this->getByToken->execute($token);
        if ($customerData && $customerData->getId()) {
            $customer = $this->customerFactory
                             ->create()
                             ->load($customerData->getId());
            return $customer;
        }
        return null;
    }

    /**
     * loadCustomerByEmail
     *
     * @param string $email
     * @return \Magento\Customer\Model\Customer
     */
    protected function loadCustomerByEmail($email)
    {
        $customer = $this->customerFactory->create()
            ->setWebsiteId($this->getWebsiteId())
            ->loadByEmail($email);
        return $customer;
    }

    /**
     * getWebsiteId
     * @return int
     */
    protected function getWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }
}
