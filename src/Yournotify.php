<?php

class Yournotify
{
    private $apiKey;
    private $apiUrl = "https://api.yournotify.com/";

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function request($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->apiUrl . $endpoint;
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
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function sendEmail($title, $subject, $html, $text, $status, $from, $to, $name, $attribs)
    {
        $data = [
            'name' => $title,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'from' => $from,
            'status' => $status,
            'channel' => "email",
            'lists' => array(
                (object)[
                    "email" => $to,
                    "name" => $name,
                    "attribs" => $attribs,
                ]
            ),
        ];
        return $this->request("campaigns/email", 'POST', $data);
    }

    public function sendSMS($title, $subject, $text, $status, $from, $to, $name, $attribs)
    {
        $data = [
            'name' => $title,
            'subject' => $subject,
            'text' => $text,
            'from' => $from,
            'status' => $status,
            'channel' => "sms",
            'lists' => array(
                (object)[
                    "telephone" => $to,
                    "name" => $name,
                    "attribs" => $attribs,
                ]
            ),
        ];
        return $this->request("campaigns/sms", 'POST', $data);
    }

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
}
