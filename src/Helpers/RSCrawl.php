<?php

namespace Roniejisa\Custom\Helpers;

use daandesmedt\PHPHeadlessChrome\HeadlessChrome;
use Illuminate\Support\Facades\Http;
use Nesk\Puphpeteer\Puppeteer;

class CrawlHelper
{
    public static $dataPuppeteer = [
        'socks5' => null,
        'proxy' => null,
    ];

    public static function puppeteer(string $url, string $socks5 = null)
    {
        $htmlDom = '';
        $configs = [
            'executable_path' => "/usr/bin/node",
            'read_timeout' => 100000,
            'idle_timeout' => 6000,
        ];

        if (request()->ip() == '127.0.0.1') {
            $configs['executable_path'] = 'C:\Program Files\nodejs\node.exe';
        }

        $puppeteer = new Puppeteer($configs);
        $arrayArguments = [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-gpu',
            '--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36',
        ];

        if ($socks5 != null) {
            $arrayArguments[] = '--proxy-server=socks5://' . $socks5;
        }

        $browser = $puppeteer->launch([
            'args' => $arrayArguments,
        ]);

        $page = $browser->newPage();

        $page->goto($url, [
            'waitUntil' => 'load',
            'timeout' => 0,
        ]);

        $htmlDom = $page->content();
        $browser->close();
        return $htmlDom;
    }

    public static function headless(string $url)
    {
        $headlessChromer = new HeadlessChrome();
        $headlessChromer->setUrl($url);

        if (request()->ip() === '127.0.0.1') {
            $headlessChromer->setBinaryPath('C:\Program Files\Google\Chrome\Application\chrome');
        } else {
            $headlessChromer->setBinaryPath('/usr/bin/google-chrome-stable');
        }
        $headlessChromer->setArgument('--user-agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36');
        $headlessChromer->setOutputDirectory(__DIR__);
        $headlessChromer->getDOM();
        return $headlessChromer->getDOM();
    }

    public static function guzzle(string $url, array $params = [], $method = 'GET', $isJson = true)
    {
        // application / x-www-form-urlencoded

        $url = Http::get($url, $params);
        if ($method == 'POST') {
            $url = Http::post($url, $params);
        }

        if (!$isJson) {
            $url->asForm();
        }

        return $url->body();
    }

    public static $curlData = [
        'method' => 'GET',
        'data' => [],
        'socks5' => null,
        'headers' => [],
    ];

    public static function curl(string $url, $data = [])
    {
        foreach ($data as $key => $value) {
            self::$curlData[$key] = $value;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        // Cấm không cho điều hướng đến trang khác
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);

        // SET PROXY
        if (self::$curlData['socks5'] != null) {
            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            [$proxy, $port] = explode(':', self::$curlData['socks5']);
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            curl_setopt($curl, CURLOPT_PROXYPORT, $port);
        }

        //SET METHOD POST
        if (self::$curlData['method'] == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
        }

        // SET HEADER
        if (count(self::$curlData['headers']) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, self::$curlData['headers']);
        }

        //SET DATA
        if (count(self::$curlData['data']) > 0) {
            $data = json_encode(self::$curlData['data'], JSON_UNESCAPED_UNICODE);
            $data = curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $output = curl_exec($curl);

        if (curl_exec($curl) === false) {
            return 'Curl error: ' . curl_error($curl);
        }

        curl_close($curl);
        return $output;
    }

}
