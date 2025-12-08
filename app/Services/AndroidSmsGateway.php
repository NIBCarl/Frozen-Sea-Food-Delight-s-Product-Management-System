<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AndroidSmsGateway implements SmsGateway
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $deviceId;

    public function __construct()
    {
        $this->baseUrl = config('services.android_sms_gateway.url');
        $this->username = config('services.android_sms_gateway.username');
        $this->password = config('services.android_sms_gateway.password');
        $this->deviceId = config('services.android_sms_gateway.device_id');
    }

    /**
     * Send an SMS to the specified phone number.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function send(string $phoneNumber, string $message): bool
    {
        try {
            Log::info("Attempting to send SMS to {$phoneNumber}");
            Log::info("SMS Gateway URL: {$this->baseUrl}");
            Log::info("Username: {$this->username}");
            Log::info("Device ID: {$this->deviceId}");
            
            // Check if configuration is present
            if (empty($this->baseUrl)) {
                Log::warning('Android SMS Gateway URL is not configured. Logging SMS instead.');
                Log::info("SMS to {$phoneNumber}: {$message}");
                return true; // Return true to avoid breaking the flow in dev
            }

            // Normalize phone number to international format (Philippine numbers)
            $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

            // Prepare the payload according to the working implementation
            $payload = [
                'deviceId' => $this->deviceId,
                'phoneNumbers' => [$normalizedPhone],
                'message' => $message,
            ];

            Log::info("SMS Payload: " . json_encode($payload));
            
            $endpoint = '/3rdparty/v1/message';

            $response = Http::timeout(10)
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl . $endpoint, $payload);

            Log::info("SMS Response Status: {$response->status()}");
            Log::info("SMS Response Body: {$response->body()}");

            if ($response->successful()) {
                Log::info("SMS sent successfully to {$phoneNumber}");
                return true;
            } else {
                Log::error("Failed to send SMS to {$phoneNumber}. Status: {$response->status()}. Body: {$response->body()}");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Exception sending SMS to {$phoneNumber}: " . $e->getMessage());
            Log::error("Exception details: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Normalize phone number to international format (Philippine numbers)
     * 
     * @param string $phoneNumber
     * @return string
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digits
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle Philippine numbers starting with 0
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '63' . substr($phoneNumber, 1);
        }
        
        // Add country code if not present
        if (substr($phoneNumber, 0, 2) !== '63') {
            $phoneNumber = '63' . $phoneNumber;
        }
        
        return '+' . $phoneNumber;
    }
}
