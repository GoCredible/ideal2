<?php

namespace PetervdBroek\iDEAL2\Resources;

use DateTime;
use Exception;

class Payment extends Base
{
    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->response['CommonPaymentData']['PaymentId'];
    }
    
    /**
     * @return string
     */
    public function getAspspPaymentId(): string
    {
        return $this->response['CommonPaymentData']['AspspPaymentId'];
    }
    
    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->response['Links']['RedirectUrl']['Href'];
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getExpiryDateTime(): DateTime
    {
        return new DateTime($this->response['CommonPaymentData']['ExpiryDateTimestamp']);
    }
}
