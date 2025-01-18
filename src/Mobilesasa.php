<?php

namespace codexoft\MobilesasaSDK;

class MobilesasaException extends \Exception
{
    protected ?array $response;
    protected ?int $statusCode;

    public function __construct(string $message, int $statusCode = null, array $response = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }
/**
 * Get the value of statusCode
 * @return int|null
 * 
 */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }
}


class Mobilesasa
{
    private $senderId;
    private $apiKey;
    private $mobileServeyKey;
    private $shortCode;
    private $showBalance;
    private $baseUrl;

    # Constants
    const networks = [
        'safaricom' => 'Safaricom PLC',
        'airtel' => 'Airtel Kenya Limited',
        'telkom' => 'Telkom Kenya Limited',
        'equitel' => 'Equitel (Finserve Africa Limited)'
    ];
    const serviceCost = [
        'phoneNumberDetails' => 0.1
    ];

    public function __construct($config)
    {
        $this->senderId = $config['senderId'];
        $this->apiKey = $config['apiKey'];
        $this->mobileServeyKey = $config['mobileServeyKey'];
        $this->showBalance = $config['showBalance'];
        $this->shortCode = $config['shortCode'];
        $this->baseUrl = 'https://api.mobilesasa.com/v1/';
    }

    public function calculateMessageLength($message)
    {
        $utf8Message = mb_convert_encoding($message, 'UTF-8');
        $length = mb_strlen($utf8Message, 'UTF-8');
        
        // Simple check for non-GSM characters using a predefined array
        $gsmChars = [
            '@', '£', '$', '¥', 'è', 'é', 'ù', 'ì', 'ò', 'Ç', 'Ø', 'ø', 'Å', 'å',
            'Δ', 'Φ', 'Γ', 'Λ', 'Ω', 'Π', 'Ψ', 'Σ', 'Θ', 'Ξ', 'Æ', 'æ', 'ß', 'É',
            ' ', '!', '"', '#', '¤', '%', '&', "'", '(', ')', '*', '+', ',', '-',
            '.', '/', ':', ';', '<', '=', '>', '?', '¡', '¿', 'A', 'B', 'C', 'D',
            'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
            'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ä', 'Ö', 'Ñ', 'Ü', '§', 'a',
            'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
            'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'ä', 'ö', 'ñ',
            'ü', 'à', "\n", "\r"
        ];
        
        $hasUnicodeCharacters = false;
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($utf8Message, $i, 1, 'UTF-8');
            if (!in_array($char, $gsmChars)) {
                $hasUnicodeCharacters = true;
                break;
            }
        }
        
        $maxLength = $hasUnicodeCharacters ? 70 : 160;
        $smsCount = ceil($length / $maxLength);
        
        return [
            'length' => $length,
            'smsCount' => $smsCount,
            'perSmsLength' => ($smsCount > 1) ? ceil($length / $smsCount) : $length,
            'encoding' => $hasUnicodeCharacters ? 'UCS-2' : 'GSM-7'
        ];
    }

    public function sendRequest($endpoint, $data, $method = 'POST')
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "authorization: Bearer " . $this->apiKey,
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $decodedResponse = @json_decode($response, true);
        $responseError = curl_error($curl);

        curl_close($curl);

        if ($responseError) {
            throw new MobilesasaException('Request failed: ' . $responseError);
        } else {
            return $decodedResponse;
        }
    }

    private function formatPhoneNumber($phoneNumber)
    {
        if (isset($phoneNumber)) {
            $numberLength = strlen($phoneNumber);
            $phoneNumber = match ($numberLength) {
                9 => "254$phoneNumber",
                10 => '254' . substr($phoneNumber, 1),
                default => (substr($phoneNumber, 0, 4) === '254') ? substr($phoneNumber, 1) : $phoneNumber,
            };
            return $phoneNumber;
        } else {
            return null;
        }
    }

    public function accountBalance()
    {
        $response = $this->sendRequest('get-balance', [], 'GET');
        if (@$response['status']) {
            return $response['balance'];
        }
    }


    public function phoneNumberDetails($phoneNumber)
    {
        if (!isset($phoneNumber)) {
            throw new MobilesasaException('Phone number is required');
        }
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $response = $this->sendRequest('msisdns/load-details', [
            'phone' => $phoneNumber
        ]);
        if (@$response['status']) {
            $data = [
                "status" => $response["status"],
                "responseCode" => $response["responseCode"],
                'formattedPhone' => $response["formattedPhone"],
                'network' => [
                    'name' => self::networks[$response["networkName"]] ?? $response["networkName"],
                    'code' => $response["networkName"]
                ],
                'cost' => $response["cost"] ?? self::serviceCost['phoneNumberDetails'],
            ];

            if ($this->showBalance) {
                $data['balance'] = (int)$this->accountBalance();
            }
            return $data;
        } else {
            return $response;
        }
    }

    public function sendSMS(string $phoneNumber, string $message,bool $isShortCode = false)
    {
        if (!isset($phoneNumber)) {
            throw new MobilesasaException('Phone number is required');
        }
        if (!isset($message)) {
            throw new MobilesasaException('Message is required');
        }

        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $messageLength = $this->calculateMessageLength($message);

         $response = $this->sendRequest('send/message', [
            'senderID' => $isShortCode ? $this->shortCode : $this->senderId,
            'phone' => $phoneNumber,
            'message' => $message
        ]);

        if (@$response['status']) {
            $data = [
                "status"=> $response["status"],
                "responseCode"=> $response["responseCode"],
                "message"=> $response["message"],
                "messageId"=> $response["messageId"],
                "messageParts"=> $messageLength['smsCount'],
                "messageLength"=> $messageLength['length'],
                "cost"=> $response["cost"] ?? null
            ];
            if ($this->showBalance) {
                $data['balance'] = (int)$this->accountBalance();
            }
            return $data;
        } else {
            return $response;
        }
    }
    public function sendBulkSms(array $phoneNumbers, string $message,bool $isShortCode = false)
    {
        if (empty($phoneNumbers)) {
            throw new MobilesasaException('Phone numbers are required');
        }
        if (!isset($message)) {
            throw new MobilesasaException('Message is required');
        }

        $messageLength = $this->calculateMessageLength($message);
        $formattedPhoneNumbers = [];
        
        foreach ($phoneNumbers as $phoneNumber) {
            $formattedPhoneNumbers[] = $this->formatPhoneNumber(trim($phoneNumber));
        }

        $response = $this->sendRequest('send/bulk', [
            'senderID' => $isShortCode ? $this->shortCode : $this->senderId,
            'phones' => implode(',', $formattedPhoneNumbers),
            'message' => $message
        ]);

        if (@$response['status']) {
            $data = [
                "status"=> $response["status"],
                "responseCode"=> $response["responseCode"],
                "message"=> $response["message"],
                "messageId"=> $response["bulkId"],
                "messageParts"=> $messageLength['smsCount'],
                "messageLength"=> $messageLength['length'],
                "cost"=> $response["cost"] ?? null
            ];
            if ($this->showBalance) {
                $data['balance'] = (int)$this->accountBalance();
            }
            return $data;
        } else {
            return $response;
        }
    }
    public function sendPersonalizedBulkSms(array $messageBody,bool $isShortCode = false)
    {
        if (empty($messageBody)) {
            throw new MobilesasaException('Message body is required');
        }

        $formattedData = [];
        $messageLength = [];
        
        foreach ($messageBody as $item) {
            if (!isset($item['phone']) || !isset($item['message'])) {
                throw new MobilesasaException('Each message must have phone and message');
            }
            
            $formattedData[] = [
                'phone' => $this->formatPhoneNumber($item['phone']),
                'message' => $item['message']
            ];
            $messageLength[] = $this->calculateMessageLength($item['message']);
        }

        $response = $this->sendRequest('send/bulk-personalized', [
            'senderID' => $isShortCode ? $this->shortCode : $this->senderId,
            'messageBody' => $formattedData
        ]);

        if (@$response['status']) {
            $data = [
                "status" => $response["status"],
                "responseCode" => $response["responseCode"],
                "message" => $response["message"],
                "messageId" => $response["bulkId"],
                "messageParts" => array_sum(array_column($messageLength, 'smsCount')),
                "messageLength" => array_sum(array_column($messageLength, 'length')),
                "cost" => $response["cost"] ?? null
            ];
            if ($this->showBalance) {
                $data['balance'] = (int)$this->accountBalance();
            }
            return $data;
        } else {
            return $response;
        }
    }
    public function smsDeliveryStatus($messageId):array
    {
        if (!isset($messageId)) {
            throw new MobilesasaException('Message ID is required');
        }

        $response = $this->sendRequest('dlr', [
            'messageId' => $messageId
        ]);

        return $response;
    }

    public function senderIdDetails(?string $senderId = null): array
    {
        if ($senderId) {
            $endpoint = 'senders/load-details';
            $data = ['senderID' => $senderId];
        } else {
            $endpoint = 'senders/load-all';
            $data = [];
        }

        $response = $this->sendRequest($endpoint, $data);
        
        return $response;
    }

    public function smsGroups(): array
    {
        $response = $this->sendRequest("groups", [], 'GET');
        
        return $response;
    }

    public function addToGroup(string $groupCode,array $contactDetails): array
    {
        if (!isset($groupCode)) {
            throw new MobilesasaException('Group code is required');
        }
        if (empty($contactDetails)) {
            throw new MobilesasaException('Contact details are required');
        }
        if (!isset($contactDetails['phone'])) {
            throw new MobilesasaException('Phone is required');
        }
        if (!isset($contactDetails['name'])) {
            throw new MobilesasaException('Name is required');
        }
        $contactDetails['phone'] = $this->formatPhoneNumber($contactDetails['phone']);
        if (isset($contactDetails['email']) && !filter_var($contactDetails['email'], FILTER_VALIDATE_EMAIL)) {
            throw new MobilesasaException('Invalid email format');
        }
        $response = $this->sendRequest("groups/{$groupCode}/add-contact", $contactDetails);

        return $response;
    }

    public function deleteFromGroup(string $groupCode,string $phone): array
    {
        if (!isset($groupCode)) {
            throw new MobilesasaException('Group code is required');
        }
        if (!isset($phone)) {
            throw new MobilesasaException('Phone is required');
        }
        $phone = $this->formatPhoneNumber($phone);
        $response = $this->sendRequest("groups/{$groupCode}/remove-contact", ['phone' => $phone], 'DELETE');

        return $response;
    }

    public function anniversaryGroups(): array
    {
        $response = $this->sendRequest("anniversary-groups", [], 'GET');
        
        return $response;
    }

    public function addToAnniversaryGroup(string $groupCode,array $contactDetails): array
    {
        if (!isset($groupCode)) {
            throw new MobilesasaException('Group code is required');
        }
        if (empty($contactDetails)) {
            throw new MobilesasaException('Contact details are required');
        }
        if (!isset($contactDetails['phone'])) {
            throw new MobilesasaException('Phone is required');
        }
        if (!isset($contactDetails['name'])) {
            throw new MobilesasaException('Name is required');
        }
        if (!isset($contactDetails['date'])) {
            throw new MobilesasaException('Date is required');
        }
        $contactDetails['date'] = date('d-m-Y', strtotime($contactDetails['date']));
        $contactDetails['phone'] = $this->formatPhoneNumber($contactDetails['phone']);
        $response = $this->sendRequest("anniversary-groups/{$groupCode}/add-contact", $contactDetails);

        return $response;
    }
    public function deleteFromAnniversaryGroup(string $groupCode,string $phone): array
    {
        if (!isset($groupCode)) {
            throw new MobilesasaException('Group code is required');
        }
        if (!isset($phone)) {
            throw new MobilesasaException('Phone is required');
        }
        $phone = $this->formatPhoneNumber($phone);
        $response = $this->sendRequest("anniversary-groups/{$groupCode}/remove-contact", ['phone' => $phone], 'DELETE');

        return $response;
    }
    public function mobileServey(string $surveyId, array $contactDetails, bool $sendNow = true, ?string $startTime = null, ?string $endTime = null): array
    {
        if (!isset($surveyId)) {
            throw new MobilesasaException('Survey ID is required');
        }
        if (empty($contactDetails)) {
            throw new MobilesasaException('Contact details are required');
        }
        if (!isset($contactDetails['phone'])) {
            throw new MobilesasaException('Phone is required');
        }
        if (!isset($contactDetails['name'])) {
            throw new MobilesasaException('Name is required');
        }
        
        if (!$sendNow) {
            if (!$startTime || !$endTime) {
                throw new MobilesasaException('Start time and end time are required for scheduled surveys');
            }
            // Validate datetime format
            if (!strtotime($startTime) || !strtotime($endTime)) {
                throw new MobilesasaException('Invalid datetime format. Use Y-m-d H:i:s');
            }
        }

        $payload = [
            'survey' => $surveyId,
            'send_now' => $sendNow,
            'start_time' => $sendNow ? null : $startTime,
            'end_time' => $sendNow ? null : $endTime,
            'members' => [
                [
                    'name' => $contactDetails['name'],
                    'phone' => $this->formatPhoneNumber($contactDetails['phone'])
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.getsurvey.co.ke/api/trigger',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Authorization: Bearer " . $this->mobileServeyKey,
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $decodedResponse = @json_decode($response, true);
        $responseError = curl_error($curl);

        curl_close($curl);

        if ($responseError) {
            throw new MobilesasaException('Request failed: ' . $responseError);
        }

        return $decodedResponse ?? [];
    }
}
