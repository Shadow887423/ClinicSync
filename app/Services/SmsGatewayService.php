<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SmsNotification;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGatewayService
{
    protected string $provider;
    protected bool $isSimulation;

    public function __construct()
    {
        $this->provider = config('sms.provider', 'simulated');
        $this->isSimulation = config('sms.simulation_enabled', true);
    }

    /**
     * Send an SMS message through the configured gateway or simulated driver
     */
    public function sendSms(
        string $recipientPhone,
        string $message,
        string $notificationType,
        ?int $appointmentId = null,
        ?int $userId = null
    ): SmsNotification {
        // Clean phone number
        $cleanPhone = $this->sanitizePhone($recipientPhone);

        // Create initial notification record in database
        $notification = SmsNotification::create([
            'appointment_id' => $appointmentId,
            'user_id' => $userId,
            'recipient_phone' => $cleanPhone,
            'notification_type' => $notificationType,
            'message' => $message,
            'status' => SmsNotification::STATUS_QUEUED,
            'gateway' => $this->provider,
            'scheduled_at' => now(),
        ]);

        return $this->dispatchNotification($notification);
    }

    /**
     * Dispatch notification to actual gateway or simulation
     */
    public function dispatchNotification(SmsNotification $notification): SmsNotification
    {
        // If simulation mode is explicitly enabled or provider is 'simulated'
        if ($this->isSimulation || $this->provider === 'simulated') {
            return $this->sendViaSimulated($notification);
        }

        return match ($this->provider) {
            'twilio' => $this->sendViaTwilio($notification),
            'semaphore' => $this->sendViaSemaphore($notification),
            'custom_http' => $this->sendViaCustomHttp($notification),
            default => $this->sendViaSimulated($notification),
        };
    }

    /**
     * Simulated driver for academic demonstration and safe testing
     */
    protected function sendViaSimulated(SmsNotification $notification): SmsNotification
    {
        try {
            $ref = 'SIM-' . strtoupper(bin2hex(random_bytes(6)));
            
            $notification->update([
                'status' => SmsNotification::STATUS_SIMULATED,
                'gateway' => 'simulated',
                'gateway_reference' => $ref,
                'sent_at' => now(),
                'payload_response' => json_encode([
                    'mode' => 'simulated',
                    'note' => 'Delivered in development/simulated gateway mode',
                    'timestamp' => now()->toIso8601String(),
                ]),
                'error_message' => null,
            ]);

            Log::channel('single')->info("[CLINICSYNC SIMULATED SMS] To: {$notification->recipient_phone} | Ref: {$ref} | Content: {$notification->message}");

            return $notification;
        } catch (Exception $e) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Simulation error: ' . $this->sanitizeErrorMessage($e->getMessage()),
            ]);
            return $notification;
        }
    }

    /**
     * Real Gateway: Twilio API
     */
    protected function sendViaTwilio(SmsNotification $notification): SmsNotification
    {
        $sid = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from = config('sms.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Twilio credentials incomplete. Configure SMS_TWILIO_SID, SMS_TWILIO_AUTH_TOKEN, and SMS_TWILIO_FROM in .env.',
            ]);
            return $notification;
        }

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(10)
                ->post($url, [
                    'To' => $notification->recipient_phone,
                    'From' => $from,
                    'Body' => $notification->message,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $notification->update([
                    'status' => SmsNotification::STATUS_SENT,
                    'gateway' => 'twilio',
                    'gateway_reference' => $body['sid'] ?? null,
                    'sent_at' => now(),
                    'payload_response' => json_encode(['status' => $body['status'] ?? 'sent']),
                    'error_message' => null,
                ]);
            } else {
                $error = $response->json();
                $notification->update([
                    'status' => SmsNotification::STATUS_FAILED,
                    'error_message' => 'Twilio API Error (' . $response->status() . '): ' . ($error['message'] ?? 'Gateway rejected request'),
                ]);
            }
        } catch (Exception $e) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Twilio transport failure: ' . $this->sanitizeErrorMessage($e->getMessage()),
            ]);
        }

        return $notification;
    }

    /**
     * Real Gateway: Semaphore SMS API
     */
    protected function sendViaSemaphore(SmsNotification $notification): SmsNotification
    {
        $apiKey = config('sms.semaphore.api_key');
        $senderName = config('sms.semaphore.sender_name', 'CLINICSYNC');

        if (empty($apiKey)) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Semaphore API key missing. Configure SMS_SEMAPHORE_API_KEY in .env.',
            ]);
            return $notification;
        }

        try {
            $response = Http::timeout(10)->post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $notification->recipient_phone,
                'message' => $notification->message,
                'sendername' => $senderName,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $ref = is_array($body) && isset($body[0]['message_id']) ? (string)$body[0]['message_id'] : 'SEM-' . time();
                $notification->update([
                    'status' => SmsNotification::STATUS_SENT,
                    'gateway' => 'semaphore',
                    'gateway_reference' => $ref,
                    'sent_at' => now(),
                    'payload_response' => json_encode($body),
                    'error_message' => null,
                ]);
            } else {
                $notification->update([
                    'status' => SmsNotification::STATUS_FAILED,
                    'error_message' => 'Semaphore API Error: ' . $response->body(),
                ]);
            }
        } catch (Exception $e) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Semaphore transport failure: ' . $this->sanitizeErrorMessage($e->getMessage()),
            ]);
        }

        return $notification;
    }

    /**
     * Real Gateway: Generic Custom HTTP SMS webhook/endpoint
     */
    protected function sendViaCustomHttp(SmsNotification $notification): SmsNotification
    {
        $url = config('sms.custom_http.url');
        $token = config('sms.custom_http.token');

        if (empty($url)) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Custom SMS Gateway URL missing. Set SMS_CUSTOM_HTTP_URL in .env.',
            ]);
            return $notification;
        }

        try {
            $req = Http::timeout(10);
            if (!empty($token)) {
                $req = $req->withToken($token);
            }

            $response = $req->post($url, [
                'to' => $notification->recipient_phone,
                'message' => $notification->message,
                'type' => $notification->notification_type,
            ]);

            if ($response->successful()) {
                $notification->update([
                    'status' => SmsNotification::STATUS_SENT,
                    'gateway' => 'custom_http',
                    'gateway_reference' => 'HTTP-' . time(),
                    'sent_at' => now(),
                    'payload_response' => $response->body(),
                    'error_message' => null,
                ]);
            } else {
                $notification->update([
                    'status' => SmsNotification::STATUS_FAILED,
                    'error_message' => 'Custom HTTP SMS Gateway responded with code ' . $response->status(),
                ]);
            }
        } catch (Exception $e) {
            $notification->update([
                'status' => SmsNotification::STATUS_FAILED,
                'error_message' => 'Custom HTTP transport failure: ' . $this->sanitizeErrorMessage($e->getMessage()),
            ]);
        }

        return $notification;
    }

    /**
     * Send Appointment Confirmation SMS
     */
    public function sendConfirmation(Appointment $appointment): ?SmsNotification
    {
        $patient = $appointment->patient;
        if (!$patient || empty($patient->phone)) {
            return null;
        }

        $dateFormatted = Carbon::parse($appointment->effective_date)->format('F d, Y');
        $timeFormatted = Carbon::parse($appointment->effective_time)->format('g:i A');
        $dentistName = $appointment->dentist ? $appointment->dentist->name : 'Assigned Clinic Dentist';

        $message = "Hello {$patient->name}, your dental appointment has been confirmed for {$dateFormatted} at {$timeFormatted}. Dentist: {$dentistName}. Please contact the clinic if you need to reschedule. — CLINICSYNC";

        return $this->sendSms(
            $patient->phone,
            $message,
            SmsNotification::TYPE_CONFIRMATION,
            $appointment->id,
            $patient->id
        );
    }

    /**
     * Send Appointment Reminder SMS
     */
    public function sendReminder(Appointment $appointment): ?SmsNotification
    {
        $patient = $appointment->patient;
        if (!$patient || empty($patient->phone)) {
            return null;
        }

        $dateFormatted = Carbon::parse($appointment->effective_date)->format('F d, Y');
        $timeFormatted = Carbon::parse($appointment->effective_time)->format('g:i A');

        $message = "Reminder: Hello {$patient->name}, your dental appointment is scheduled for {$dateFormatted} at {$timeFormatted}. Please contact the clinic if you need to reschedule. — CLINICSYNC";

        $notification = $this->sendSms(
            $patient->phone,
            $message,
            SmsNotification::TYPE_REMINDER,
            $appointment->id,
            $patient->id
        );

        if (in_array($notification->status, [SmsNotification::STATUS_SENT, SmsNotification::STATUS_SIMULATED, SmsNotification::STATUS_DELIVERED])) {
            $appointment->update(['reminder_sent_at' => now()]);
        }

        return $notification;
    }

    /**
     * Send Rescheduled SMS
     */
    public function sendRescheduledNotice(Appointment $appointment): ?SmsNotification
    {
        $patient = $appointment->patient;
        if (!$patient || empty($patient->phone)) {
            return null;
        }

        $dateFormatted = Carbon::parse($appointment->effective_date)->format('F d, Y');
        $timeFormatted = Carbon::parse($appointment->effective_time)->format('g:i A');
        $dentistName = $appointment->dentist ? $appointment->dentist->name : 'Assigned Clinic Dentist';

        $message = "Hello {$patient->name}, your dental appointment has been rescheduled to {$dateFormatted} at {$timeFormatted} with {$dentistName}. — CLINICSYNC";

        return $this->sendSms(
            $patient->phone,
            $message,
            SmsNotification::TYPE_RESCHEDULED,
            $appointment->id,
            $patient->id
        );
    }

    /**
     * Send Cancellation SMS
     */
    public function sendCancellationNotice(Appointment $appointment, string $reason = ''): ?SmsNotification
    {
        $patient = $appointment->patient;
        if (!$patient || empty($patient->phone)) {
            return null;
        }

        $dateFormatted = Carbon::parse($appointment->effective_date)->format('F d, Y');
        $reasonText = !empty($reason) ? " Reason: {$reason}." : '';

        $message = "Notice: Hello {$patient->name}, your dental appointment scheduled for {$dateFormatted} has been cancelled.{$reasonText} Please contact us to rebook. — CLINICSYNC";

        return $this->sendSms(
            $patient->phone,
            $message,
            SmsNotification::TYPE_CANCELLATION,
            $appointment->id,
            $patient->id
        );
    }

    /**
     * Send Rejection SMS
     */
    public function sendRejectionNotice(Appointment $appointment, string $reason = ''): ?SmsNotification
    {
        $patient = $appointment->patient;
        if (!$patient || empty($patient->phone)) {
            return null;
        }

        $dateFormatted = Carbon::parse($appointment->requested_date)->format('F d, Y');
        $reasonText = !empty($reason) ? " Note: {$reason}." : '';

        $message = "Hello {$patient->name}, your appointment request for {$dateFormatted} could not be confirmed.{$reasonText} Please log in to select an alternative slot. — CLINICSYNC";

        return $this->sendSms(
            $patient->phone,
            $message,
            SmsNotification::TYPE_REJECTION,
            $appointment->id,
            $patient->id
        );
    }

    /**
     * Retry sending a failed notification
     */
    public function retryNotification(SmsNotification $notification): SmsNotification
    {
        return $this->dispatchNotification($notification);
    }

    /**
     * Sanitize phone number to standard format
     */
    protected function sanitizePhone(string $phone): string
    {
        // Remove spaces, hyphens, parentheses
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        return $cleaned;
    }

    /**
     * Prevent leaking secrets or API tokens in log messages
     */
    protected function sanitizeErrorMessage(string $error): string
    {
        $token = env('SMS_TWILIO_AUTH_TOKEN');
        $apiKey = env('SMS_SEMAPHORE_API_KEY');
        $customToken = env('SMS_CUSTOM_HTTP_TOKEN');

        $replacements = array_filter([$token, $apiKey, $customToken]);
        if (!empty($replacements)) {
            return str_replace($replacements, '[REDACTED]', $error);
        }

        return $error;
    }
}
