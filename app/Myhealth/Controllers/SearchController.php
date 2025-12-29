<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\Common;
use Myhealth\Classes\AjaxResponse;

class SearchController
{
    private $apiUrl;
    private $apiKey;

    public function doctorSearch()
    {
        $cmd = Request('c');
        
        if ($cmd !== '') {
            $this->processCommand($cmd);
        }

        View::render('doctor-search', 'Doctor Search');
    }

    private function processCommand(string $cmd)
    {
        $results = null;

        if ($cmd === 'getspecialties') {
            $results = $this->getSpecialties();
        }
        elseif ($cmd === 'provsearch') {
            $results = $this->search();
        }

        if (is_null($results)) {
            return;
        }

        if (!empty($results->error)) {
            AjaxResponse::error($results->error);
        }

        AjaxResponse::response($results);
    }

    private function apiConfig(): bool
    {
        $common = new Common();
        $domain = getDomain();
        $this->apiUrl = $common->getConfig('QCPORTAL API URL', $domain);
        $this->apiKey = $common->getConfig('QCPORTAL API KEY', $domain);

        return (!empty($this->apiUrl) && !empty($this->apiKey));
    }

    private function getSpecialties(): object
    {
        return $this->runApiCommand('getspecialties', ['c' => 'getspecialties']);
    }

    private function search(): object
    {
        $params = [
            'c' => 'provsearch',
            'last' => Request('last'),
            'first' => Request('first'),
            'spec' => Request('spec'),
            'language' => Request('language'),
            'city' => Request('city'),
            'zip' => Request('zip'),
            'radius' => Request('radius'),
            'pcp' => Request('pcp'),
            'gender' => Request('gender'),
            'morning' => Request('morning'), 
            'evening' => Request('evening'),
            'weekend' => Request('weekend'),
        ];

        return $this->runApiCommand('getspecialties', $params);
    }

    private function runApiCommand(string $cmd, array $params): object
    {
        if (!$this->apiConfig()) {
            return (object) ['error' => 'Doctor search not configured properly'];
        }

        $paramString = "apikey={$this->apiKey}&".http_build_query($params);
        $response = http('GET', $this->apiUrl, $paramString);

        if ($response->status != 200) {
            return (object) ['error' => $response->error];
        }

        return json_decode($response->response);
    }
}