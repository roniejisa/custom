<?php

namespace Roniejisa\Custom\Helpers;

use daandesmedt\PHPHeadlessChrome\HeadlessChrome;
use function GuzzleHttp\Promise\settle;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Nesk\Puphpeteer\Puppeteer;
use Psr\Http\Message\ResponseInterface;

class RSCrawl
{
    private static $defaultData = [
        'method' => 'GET',
        'data' => [],
        'proxy' => null,
        'headers' => [],
        'isJson' => true,
        'getContent' => true,
        'hasRedirect' => false,
    ];

    public static function puppeteer(string $url, array $data = [])
    {
        try {
            self::setDefaultData($data);

            $configs = [
                'executable_path' => "/usr/bin/node",
                'idle_timeout' => 10000,
                'read_timeout' => 10000,
                'logger' => null,
                'debug' => false,
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
                '--ignore-certificate-errors',
                '--ignore-certificate-errors-spki-list',
            ];

            if (!is_null(self::$defaultData['proxy'])) {
                $arrayArguments[] = '--proxy-server=proxy://' . self::$defaultData['proxy'];
            }

            $browser = $puppeteer->launch([
                'args' => $arrayArguments,
                "headless" => true,
                "ignoreHTTPSErrors" => true,
            ]);

            $page = $browser->newPage();

            $page->goto($url, [
                'waitUntil' => 'load',
                'timeout' => 0,
            ]);

            $htmlDom = $page->content();
            $browser->close();
            return $htmlDom;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function headless(string $url, array $data = [])
    {
        try {

            self::setDefaultData($data);

            $headlessChromer = new HeadlessChrome();
            $headlessChromer->setUrl($url);

            if (request()->ip() === '127.0.0.1') {
                $headlessChromer->setBinaryPath('C:\Program Files\Google\Chrome\Application\chrome');
            } else {
                $headlessChromer->setBinaryPath('/usr/bin/google-chrome-stable');
            }
            $headlessChromer->setArgument('--user-agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36');
            if (!is_null(self::$defaultData['proxy'])) {
                $headlessChromer->setArgument('--proxy-server', self::$defaultData['proxy']);
            }
            $headlessChromer->setOutputDirectory(__DIR__);
            return $headlessChromer->getDOM();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function guzzle(string $url, array $data = [])
    {
        // application / x-www-form-urlencoded
        try {
            self::setDefaultData($data);
            $defaultGuzzle = Http::withOptions([
                'allow_redirects' => false,
            ]);
            if (count(self::$defaultData['headers']) > 0) {
                $defaultGuzzle->withHeaders(self::$defaultData['headers']);
            }

            if (!self::$defaultData['isJson']) {
                $defaultGuzzle->asForm();
            }

            self::setData($url);

            $response = $defaultGuzzle->get($url, self::$defaultData['data']);
            if (self::$defaultData['method'] == 'POST') {
                $response = $defaultGuzzle->post($url, self::$defaultData['data']);
            }
            if (!self::$defaultData['getContent']) {
                return $response;
            }
            return $response->body();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function curl(string $url, array $data = [])
    {
        try {

            self::setDefaultData($data);

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            // Cấm không cho điều hướng đến trang khác
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);

            // SET PROXY
            if (!is_null(self::$defaultData['proxy'])) {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                [$proxy, $port] = explode(':', self::$defaultData['proxy']);
                curl_setopt($curl, CURLOPT_PROXY, $proxy);
                curl_setopt($curl, CURLOPT_PROXYPORT, $port);
            }

            //SET METHOD POST
            if (self::$defaultData['method'] == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
            }

            // SET HEADER
            if (count(self::$defaultData['headers']) > 0) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, self::$defaultData['headers']);
            }

            //SET DATA
            if (count(self::$defaultData['data']) > 0) {
                $data = json_encode(self::$defaultData['data'], JSON_UNESCAPED_UNICODE);
                $data = curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }

            $output = curl_exec($curl);

            if (curl_exec($curl) === false) {
                return 'Curl error: ' . curl_error($curl);
            }

            curl_close($curl);
            return $output;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function asyncRequest(array $listUrl, array $data = [])
    {
        self::setDefaultData($data);

        $client = new Client([
            'timeout' => 0,
            'verify' => false,
        ]);

        $promises = [];
        // Xử lý các url trong danh sách
        foreach ($listUrl as $key => $url) {
            self::setData($url);

            $options = [
                'headers' => self::$defaultData['headers'],
                'query' => self::$defaultData['data'],
                'allow_redirects' => self::$defaultData['hasRedirect'],
            ];

            if (!is_null(self::$defaultData['proxy'])) {
                $options['proxy'] = self::$defaultData['proxy'];
            }

            $promises[$key] = $client->requestAsync(self::$defaultData['method'], $url, $options)->then(
                function (ResponseInterface $res) {
                    return $res->getBody()->getContents();
                }
            );
        }
        return settle($promises)->wait();
    }

    public static function setDefaultData($data)
    {
        foreach ($data as $key => $value) {
            self::$defaultData[$key] = $value;
        }
    }

    public static function setData(string $url)
    {
        $parseUrl = parse_url($url);
        if (isset($parseUrl['query'])) {
            parse_str($parseUrl['query'], $params);
            foreach ($params as $key => $value) {
                self::$defaultData['data'][$key] = $value;
            }
        }
    }
}