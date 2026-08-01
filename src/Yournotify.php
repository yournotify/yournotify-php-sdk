<?php

class Yournotify
{
    private $apiKey;
    private $apiUrl = "https://api.yournotify.com/";

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        return $this;
    }

    private function request($endpoint, $method = 'GET', $data = [])
    {
        $method = strtoupper($method);
        $url = $this->apiUrl . ltrim($endpoint, '/');
        if ($method === 'GET' && !empty($data)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data);
        }
        $ch = curl_init($url);

        $headers = [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $body = json_decode($response ?: '{}', true) ?: [];
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException($body['message'] ?? "Yournotify API request failed with status {$status}.", $status);
        }

        return $body;
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
    public function getLoyaltyPrograms($params = []) { return $this->request('loyalty/programs', 'GET', $params); }
    public function getLoyaltyProgram($id) { return $this->request("loyalty/programs/{$id}", 'GET'); }
    public function createLoyaltyProgram($data = []) { return $this->request('loyalty/programs', 'POST', $data); }
    public function updateLoyaltyProgram($id, $data = []) { return $this->request("loyalty/programs/{$id}", 'PUT', $data); }
    public function getLoyaltyMembers($id, $params = []) { return $this->request("loyalty/programs/{$id}/members", 'GET', $params); }
    public function adjustLoyaltyPoints($id, $data = []) { return $this->request("loyalty/programs/{$id}/points", 'POST', $data); }
    public function trackLoyaltyEvent($id, $data = []) { return $this->request("loyalty/programs/{$id}/events", 'POST', $data); }
    public function redeemLoyaltyReward($id, $data = []) { return $this->request("loyalty/programs/{$id}/redeem", 'POST', $data); }
    public function getReferralPrograms($params = []) { return $this->request('referrals/programs', 'GET', $params); }
    public function getReferralProgram($id) { return $this->request("referrals/programs/{$id}", 'GET'); }
    public function createReferralProgram($data = []) { return $this->request('referrals/programs', 'POST', $data); }
    public function updateReferralProgram($id, $data = []) { return $this->request("referrals/programs/{$id}", 'PUT', $data); }
    public function deleteReferralProgram($id) { return $this->request("referrals/programs/{$id}", 'DELETE'); }
    public function getReferralAdvocates($id, $params = []) { return $this->request("referrals/programs/{$id}/advocates", 'GET', $params); }
    public function addReferralAdvocate($id, $data = []) { return $this->request("referrals/programs/{$id}/advocates", 'POST', $data); }
    public function trackReferralEvent($id, $data = []) { return $this->request("referrals/programs/{$id}/events", 'POST', $data); }
    public function getReferralAnalytics($id, $params = []) { return $this->request("referrals/programs/{$id}/analytics", 'GET', $params); }
    public function identify($data = []) { return $this->request('automations/identify', 'POST', $data); }
    public function track($data = []) { return $this->request('automations/events', 'POST', $data); }
}
