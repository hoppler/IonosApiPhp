<?php

namespace IonosApi {
    use GuzzleHttp\TransferStats;

    require 'vendor/autoload.php';

    class IonosUpdater
    {
        const URL = 'https://mein.ionos.de/';
        const URL_LOGIN = 'https://login.ionos.de/';
        const DOMAIN_SETTINGS_URL = 'domain-dns-settings/';
        const DOMAIN_SETTINGS_EDIT_URL = 'edit-dns-record/';
        
        private $client;

        public function __construct()
        {
            $this->client = new \GuzzleHttp\Client(['cookies' => true]);
        }

        public function Login($username, $password)
        {
            $loginResultUrl = '';
            $postContent = [
                'form_params' => [
                    '__lf' => 'Login',
                    '__sendingdata' => 1,
                    'oaologin.username' => $username,
                    'oaologin.password' => $password
                ],
                'on_stats' => function (TransferStats $stats) use (&$loginResultUrl) {
                    $loginResultUrl = $stats->getEffectiveUri();
                }
            ];

            $this->client->post(IonosUpdater::URL_LOGIN, $postContent);
            $loginSuccess = strpos(IonosUpdater::URL_LOGIN, (string)$loginResultUrl) === false; //returns 0 as position, so === to get real false

            return $loginSuccess;
        }

        public function Logout()
        {
            if ($this->client == null)
                return;

            $url = IonosUpdater::URL . 'Logout';
            $this->client->get($url);
        }

        public function UpdateARecord($rootDomain, $domain, $ip, $ttl, $isWwwRecord)
        {
            $dnsRecord = $this->GetDnsRecord($rootDomain, $domain);
            $recId = $dnsRecord['record_id'];
            $recIp = $dnsRecord['record_ip'];

            if ($recId == null) {
                echo 'Domain ' . $domain . 'not found for ' . $rootDomain;
                return false;
            }
            if ($recIp == $ip) {
                echo 'Domain ' . $domain . ': Current ' . $recIp . ' and new IP ' . $ip . ' are the same';
                return true;
            }

            echo 'Updating Domain: ' . $domain . ' to IP: ' . $ip;
            $url = IonosUpdater::URL . IonosUpdater::DOMAIN_SETTINGS_EDIT_URL . $rootDomain . '/' . $recId;
            $postContent = [
                'form_params' => [
                    '__sendingdata' => 1,
                    'record.forWwwSubdomain' => $isWwwRecord ? 'true' : 'false',
                    'record.value' => $ip,
                    'record.ttl' => $ttl
                ]
            ];
            $this->client->post($url, $postContent);

            $changeDnsRecord = $this->GetDnsRecord($rootDomain, $domain);
            $changeRecIp = $changeDnsRecord['record_ip'];

            return $ip === $changeRecIp;
        }

        private function GetDnsRecord($rootDomain, $domain)
        {
            $urlParameter = [
                'query' => [
                    'page.size' => 50,
                    'page.page' => 0,
                    'filter.host' => $domain,
                    'filter.search' => 'A'
                ]
            ];

            $url = IonosUpdater::URL . IonosUpdater::DOMAIN_SETTINGS_URL . $rootDomain;
            $response = $this->client->get($url, $urlParameter);

            $respHtml = $response->getBody();

            if (!$respHtml) {
                echo "Failed to change IP";
                return ['record_id' => null, 'record_ip' => null];
            }

            return $this->ParseDnsHtmlTable($respHtml);
        }

        private function ParseDnsHtmlTable(&$html)
        {
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML($html);

            $xpath = new \DOMXPath($doc);
            $trs = $xpath->query('//table[contains(@class, "content-table")]/tbody//tr[contains(@class, "table__row--enabled")]');
            $record_id = null;
            $record_ip = null;
            foreach ($trs as $tr) {
                $aTags = $xpath->query('.//a', $tr);
                if (count($aTags) <= 0)
                    continue;
                if ($aTags[0]->nodeValue != 'A')
                    continue;
                $aTagHref = $xpath->query('./@href', $aTags[2])[0];
                $tdTags = $xpath->query('.//td', $tr);
                $record_id = explode('/', $aTagHref->nodeValue);
                $record_id = explode('?', end($record_id))[0];
                $record_ip = $tdTags[3]->nodeValue;
                break;
            }

            return ['record_id' => $record_id, 'record_ip' => $record_ip];
        }
    }
}