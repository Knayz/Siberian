<?php

namespace PaymentStripe\Model;

use PaymentMethod\Model\GatewayAbstract;

/**
 * Class Stripe
 * @package PaymentStripe\Model
 */
class Stripe extends GatewayAbstract
{
    /**
     * @var array
     */
    public static $paymentMethod = "credit-card";

    /**
     * @param null $appId
     * @return bool
     */
    public function isSetup($appId = null)
    {
        return Application::isAvailable($appId);
    }
}