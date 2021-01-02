<?php
/**
 * Greetings from Wamoco GmbH, Bremen, Germany.
 * @author Wamoco Team<info@wamoco.de>
 * @license See LICENSE.txt for license details.
 */

namespace Wamoco\ForcePasswordReset\Model;

/**
 * Class: Config
 */
class Config
{
    /**
     * getTemplate
     * @return string
     */
    public function getTemplate()
    {
        return \Magento\Customer\Model\AccountManagement::EMAIL_REMINDER;
    }

    /**
     * getMessage
     * @return string
     */
    public function getMessage()
    {
        return __("Due to security reasons, you need to reset your password: we send you an email. Please follow the instructions.");
    }
}
