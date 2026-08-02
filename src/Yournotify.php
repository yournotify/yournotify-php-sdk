<?php

class Yournotify
{
    private $apiKey;
    private $apiUrl = "https://api.yournotify.com/";
    private $timeout = 30;
    private $maxRetries = 2;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        return $this;
    }

    public function setTimeout($seconds) { $this->timeout = max(1, (int) $seconds); return $this; }
    public function setMaxRetries($attempts) { $this->maxRetries = max(0, (int) $attempts); return $this; }

    private function request($endpoint, $method = 'GET', $data = [])
    {
        $method = strtoupper($method);
        $url = $this->apiUrl . ltrim($endpoint, '/');
        if ($method === 'GET' && !empty($data)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data);
        }
        $headers = [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ];
        $idempotencyKey = is_array($data) ? ($data['idempotency_key'] ?? $data['event_id'] ?? null) : null;
        if ($idempotencyKey) $headers[] = "Idempotency-Key: " . $idempotencyKey;
        $retryable = in_array($method, ['GET', 'HEAD', 'PUT', 'DELETE'], true) || !empty($idempotencyKey);

        for ($attempt = 0; ; $attempt++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if ($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
            elseif ($method !== 'GET') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!in_array($method, ['GET', 'HEAD', 'DELETE'], true)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            $body = json_decode($response ?: '{}', true) ?: [];
            if ($status >= 200 && $status < 300) return $body;
            $canRetry = $retryable && $attempt < $this->maxRetries && ($status === 0 || $status === 429 || $status >= 500);
            if (!$canRetry) {
                throw new \RuntimeException($body['message'] ?? ($curlError ?: "Yournotify API request failed with status {$status}."), $status);
            }
            usleep(250000 * (2 ** $attempt));
        }
    }

    private function normalizeList($value, $key)
    {
        if ($value === null) return [];
        $items = is_array($value) ? $value : [$value];
        return array_map(function ($item) use ($key) {
            return is_string($item) ? [$key => $item] : $item;
        }, $items);
    }

    public function validateAuth() { return $this->request('auth/me'); }
    public function getProfile() { return $this->validateAuth(); }
    public function createCampaign($data = []) { return $this->request('campaigns', 'POST', $data); }
    public function sendVoice($data = []) { return $this->request('campaigns/voice', 'POST', $data); }

    public function sendEmail($title, $subject, $html, $text, $status, $from, $to, $name, $attribs)
    {
        return $this->createCampaign([
            'name' => $title,
            'subject' => $subject,
            'html' => $html,
            'body' => $html,
            'text' => $text,
            'from' => $from,
            'from_email' => $from,
            'status' => $status,
            'channel' => "email",
            'lists' => [['email' => $to, 'name' => $name, 'attribs' => $attribs]],
        ]);
    }

    public function sendSMS($title, $subject, $text, $status, $from, $to, $name, $attribs)
    {
        return $this->createCampaign([
            'name' => $title,
            'subject' => $subject,
            'text' => $text,
            'body' => $text,
            'from' => $from,
            'sender' => $from,
            'status' => $status,
            'channel' => "sms",
            'lists' => [['telephone' => $to, 'name' => $name, 'attribs' => $attribs]],
        ]);
    }

    public function sendWhatsApp($data = []) { $data['channel'] = 'whatsapp'; return $this->createCampaign($data); }
    public function sendPush($data = []) { $data['channel'] = 'push'; return $this->createCampaign($data); }
    public function sendInApp($data = []) { $data['channel'] = 'inapp'; return $this->createCampaign($data); }
    public function testCampaign($data = []) { return $this->request('campaigns/test', 'POST', $data); }

    public function addContact($email, $telephone, $list, $name, $attribs)
    {
        $data = [
            'email' => $email,
            'telephone' => $telephone,
            'lists' => [$list],
            'name' => $name,
            'attribs' => $attribs
        ];
        return $this->request("contacts", 'POST', $data);
    }

    public function getContact($id)
    {
        return $this->request("contacts/" . $id, 'GET');
    }

    public function getContacts()
    {
        return $this->request("contacts", 'GET');
    }

    public function updateContact($id, $email = null, $telephone = null, $lists = [], $name = '', $attribs = [])
    {
        return $this->request("contacts/" . $id, 'PUT', compact('email', 'telephone', 'lists', 'name', 'attribs'));
    }

    public function deleteContact($id)
    {
        return $this->request("contacts/" . $id, 'DELETE');
    }

    public function addList($title, $type, $optin)
    {
        $data = [
            'title' => $title,
            'type' => $type,
            'optin' => $optin
        ];
        return $this->request("lists", 'POST', $data);
    }

    public function getList($id)
    {
        return $this->request("lists/" . $id, 'GET');
    }

    public function getLists()
    {
        return $this->request("lists", 'GET');
    }

    public function updateList($id, $title)
    {
        return $this->request("lists/" . $id, 'PUT', ['name' => $title, 'title' => $title]);
    }

    public function getCampaign($id) { return $this->request("campaigns/" . $id, 'GET'); }
    public function getCampaigns($channel = null, $params = []) { if ($channel) $params['channel'] = $channel; return $this->request('campaigns', 'GET', $params); }
    public function updateCampaign($id, $data = []) { return $this->request("campaigns/" . $id, 'PUT', $data); }

    public function deleteList($id)
    {
        return $this->request("lists/" . $id, 'DELETE');
    }

    public function deleteCampaign($id)
    {
        return $this->request("campaigns/" . $id, 'DELETE');
    }

    public function getCampaignStats($ids, $channel = 'email')
    {
        $idParam = is_array($ids) ? implode(',', $ids) : $ids;
        return $this->request("campaigns/{$idParam}/analytics/stats", 'GET', ['channel' => $channel]);
    }

    public function getCampaignReports($ids, $channel = 'email')
    {
        $idParam = is_array($ids) ? implode(',', $ids) : $ids;
        return $this->request("campaigns/{$idParam}/analytics/reports", 'GET', ['channel' => $channel]);
    }

    public function getRewards($params = []) { return $this->request('rewards', 'GET', $params); }
    public function createReward($data = []) { return $this->request('rewards', 'POST', $data); }
    public function sendReward($data = []) { return $this->request('rewards/send', 'POST', $data); }
    public function getRewardProducts($params = []) { return $this->request('rewards/products', 'GET', $params); }
    public function getRewardAnalytics($id) { return $this->request("rewards/{$id}/analytics", 'GET'); }
    public function getRewardSubmissions($id, $params = []) { return $this->request("rewards/{$id}/submissions", 'GET', $params); }
    public function inviteToReward($id, $data = []) { return $this->request("rewards/{$id}/invite", 'POST', $data); }
    public function sendCreatedReward($id, $data = []) { return $this->request("rewards/{$id}/send", 'POST', $data); }
    public function getRewardBulkJob($id, $jobId) { return $this->request("rewards/{$id}/bulk-jobs/{$jobId}", 'GET'); }
    public function retryRewardBulkJob($id, $jobId) { return $this->request("rewards/{$id}/bulk-jobs/{$jobId}/retry", 'POST', []); }
    public function bootstrapRewardClaim($data = []) { return $this->request('rewards/reward', 'POST', $data); }
    public function submitRewardClaim($data = []) { return $this->request('rewards/submit', 'POST', $data); }
    public function getLoyaltyPrograms($params = []) { return $this->request('loyalty/programs', 'GET', $params); }
    public function getLoyaltyProgram($id) { return $this->request("loyalty/programs/{$id}", 'GET'); }
    public function createLoyaltyProgram($data = []) { return $this->request('loyalty/programs', 'POST', $data); }
    public function updateLoyaltyProgram($id, $data = []) { return $this->request("loyalty/programs/{$id}", 'PUT', $data); }
    public function getLoyaltyMembers($id, $params = []) { return $this->request("loyalty/programs/{$id}/members", 'GET', $params); }
    public function getLoyaltyMember($id, $subscriberId) { return $this->request("loyalty/programs/{$id}/members/{$subscriberId}", 'GET'); }
    public function adjustLoyaltyPoints($id, $data = []) { return $this->request("loyalty/programs/{$id}/points", 'POST', $data); }
    public function trackLoyaltyEvent($id, $data = []) { return $this->request("loyalty/programs/{$id}/events", 'POST', $data); }
    public function addLoyaltyRule($id, $data = []) { return $this->request("loyalty/programs/{$id}/rules", 'POST', $data); }
    public function connectLoyaltyReward($id, $data = []) { return $this->request("loyalty/programs/{$id}/rewards", 'POST', $data); }
    public function redeemLoyaltyReward($id, $data = []) { return $this->request("loyalty/programs/{$id}/redeem", 'POST', $data); }
    public function getReferralPrograms($params = []) { return $this->request('referrals/programs', 'GET', $params); }
    public function getReferralProgram($id) { return $this->request("referrals/programs/{$id}", 'GET'); }
    public function createReferralProgram($data = []) { return $this->request('referrals/programs', 'POST', $data); }
    public function updateReferralProgram($id, $data = []) { return $this->request("referrals/programs/{$id}", 'PUT', $data); }
    public function deleteReferralProgram($id) { return $this->request("referrals/programs/{$id}", 'DELETE'); }
    public function getReferralAdvocates($id, $params = []) { return $this->request("referrals/programs/{$id}/advocates", 'GET', $params); }
    public function addReferralAdvocate($id, $data = []) { return $this->request("referrals/programs/{$id}/advocates", 'POST', $data); }
    public function addReferralAdvocatesFromLists($id, $data = []) { return $this->request("referrals/programs/{$id}/advocates/bulk", 'POST', $data); }
    public function removeReferralAdvocate($id, $advocateId) { return $this->request("referrals/programs/{$id}/advocates/{$advocateId}", 'DELETE'); }
    public function trackReferralEvent($id, $data = []) { return $this->request("referrals/programs/{$id}/events", 'POST', $data); }
    public function getReferralAnalytics($id, $params = []) { return $this->request("referrals/programs/{$id}/analytics", 'GET', $params); }
    public function retryReferralConversion($id, $conversionId) { return $this->request("referrals/programs/{$id}/conversions/{$conversionId}/retry", 'POST', []); }
    public function reviewReferralConversion($id, $conversionId, $data = []) { return $this->request("referrals/programs/{$id}/conversions/{$conversionId}/review", 'POST', $data); }
    public function getReferralRisk($id) { return $this->request("referrals/programs/{$id}/risk", 'GET'); }
    public function createAdvocatePortalSession($id, $advocateId) { return $this->request("referrals/programs/{$id}/advocates/{$advocateId}/portal-session", 'POST', []); }
    public function identify($data = []) { return $this->request('sdk/identify', 'POST', $data); }
    private function normalizeEvent($data) { $data = is_array($data) ? $data : []; $data['event_id'] = $data['event_id'] ?? $data['idempotency_key'] ?? bin2hex(random_bytes(16)); $data['occurred_at'] = $data['occurred_at'] ?? gmdate('Y-m-d\TH:i:s.v\Z'); return $data; }
    public function track($data = []) { return $this->request('sdk/events', 'POST', $this->normalizeEvent($data)); }
    public function trackBatch($events = [], $options = []) { return $this->request('sdk/events/batch', 'POST', array_merge($options, ['events' => array_map([$this, 'normalizeEvent'], $events)])); }
    public function aliasContact($data = []) { return $this->request('sdk/alias', 'POST', $data); }
    public function contactSummary($params = []) { return $this->request('contacts/summary', 'GET', $params); }
    public function createContactSession($data = []) { return $this->request('contacts/session', 'POST', $data); }
    public function exportList($id, $params = []) { return $this->request("lists/export/{$id}", 'GET', $params); }
    public function retryListImport($id) { return $this->request("lists/{$id}/import/requeue", 'POST', []); }
    public static function verifyWebhook($payload, $signature, $timestamp, $secret, $tolerance = 300)
    {
        $parts = []; foreach (explode(',', (string) $signature) as $part) { if (strpos($part, '=') !== false) { [$key, $value] = explode('=', $part, 2); $parts[$key] = $value; } }
        $timestamp = $timestamp ?: ($parts['t'] ?? ''); $signature = $parts['v1'] ?? $signature;
        if (!$signature || !$timestamp || !$secret || abs(time() - (int) $timestamp) > $tolerance) return false;
        $raw = is_string($payload) ? $payload : json_encode($payload);
        $expected = hash_hmac('sha256', $timestamp . '.' . $raw, $secret);
        return hash_equals($expected, preg_replace('/^sha256=/', '', $signature));
    }
}
