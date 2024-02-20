<?php

namespace PetervdBroek\iDEAL2\Endpoints;

class Payment extends Base
{
    protected string $endpoint = '/xs2a/routingservice/services/ob/pis/v3/payments';
    protected string $method = 'POST';

    private float $amount;
    private string $reference;
    private string $notificationUrl;
    private string $returnUrl;
    private string $issuerId;
    private string $description;

    /**
     * @param float $amount
     * @param string $reference
     * @param string $notificationUrl
     * @param string $returnUrl
     * @return void
     */
    public function initialize(float $amount, string $reference, string $notificationUrl, string $returnUrl, string $issuerId,  string $description): void
    {
        $this->amount = $amount;
        $this->reference = $reference;
        $this->notificationUrl = $notificationUrl;
        $this->returnUrl = $returnUrl;
        $this->issuerId = $issuerId;
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [
            'headers' => $this->getHeaders(),
            'json' => json_decode($this->getBody()),
        ];
    }

    /**
     * @return string
     */
    protected function getBody(): string
    {
        return json_encode([
            'PaymentProduct' => ['IDEAL'],
            'CommonPaymentData' => [
                'Amount' => [
                    'Type' => 'Fixed',
                    'Amount' => number_format($this->amount, 2, '.', ''),
                    'Currency' => 'EUR',
                ],
                'DebtorInformation' =>[
                    'Agent' => $this->issuerId
                ],
                'RemittanceInformation' => $this->description,
                'RemittanceInformationStructured' => ['Reference' => $this->reference],
            ],
            'IDEALPayments' => [
                'UseDebtorToken' => false,
                'FlowType' => 'Standard'
            ]
        ]);
    }

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Digest' => $this->getDigest(),
            'X-Request-ID' => $this->requestId,
            'MessageCreateDateTime' => date('c'),
            'InitiatingPartyNotificationUrl' => $this->notificationUrl,
            'InitiatingPartyReturnUrl' => $this->returnUrl,
        ];
    }
}
