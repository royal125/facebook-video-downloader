<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

header('Content-Type: application/json');

$msg = [];

try {
    $url = $_REQUEST['url'];
    $redirectUrl = null;

    if (empty($url)) {
        throw new Exception('Please provide the URL', 1);
    }

    $headers = [
        'sec-fetch-user'            => '?1',
        'sec-ch-ua-mobile'          => '?0',
        'sec-fetch-site'            => 'none',
        'sec-fetch-dest'            => 'document',
        'sec-fetch-mode'            => 'navigate',
        'cache-control'             => 'max-age=0',
        'authority'                 => 'www.facebook.com',
        'upgrade-insecure-requests' => '1',
        'accept-language'           => 'en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6',
        'sec-ch-ua'                 => '"Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
        'user-agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
        'accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'cookie'                    => 'datr=4i_YY1yhzOIze0gz2NB3Dygf; sb=2ZLYY_Fux3tS3SwUGVw4u7H7; dpr=1.25; c_user=100027831305378; usida=eyJ2ZXIiOjEsImlkIjoiQXJwYzc2cnQ5c2w2MyIsInRpbWUiOjE2NzUxNDY2MzV9; presence=C%7B%22t3%22%3A%5B%5D%2C%22utc3%22%3A1675429370918%2C%22v%22%3A1%7D; xs=15%3AtpgPKlU6RRRhhw%3A2%3A1675137780%3A-1%3A-1%3A%3AAcVfSHhY9E8asHuAYb11Y786oQsX72Y-99IXnZuAuA; fr=08tSknIXoXdrWncKT.AWUlBw39PKXG0CFSYAlhEtkdtlM.Bj3QX8.q4.AAA.0.0.Bj3QX8.AWWQqy6Rnc8; wd=982x754',
    ];

    $redirectCheckClient = HttpClient::create([
        'headers' => $headers,
    ]);

    $client = HttpClient::create([
        'headers' => $headers,
    ]);

    $response = $client->request('GET', $url);

    $data = $response->getContent();

    $msg['success'] = true;

    $msg['id'] = generateId($url);
    $msg['title'] = getTitle($data);

    if ($sdLink = getSDLink($data)) {
        $msg['links']['Download Low Quality'] = $sdLink;
    }

    if ($hdLink = getHDLink($data)) {
        $msg['links']['Download High Quality'] = $hdLink;
    }
} catch (Exception $e) {
    $msg['success'] = false;
    $msg['message'] = $e->getMessage();
}

echo json_encode($msg);

function generateId($url)
{
    $id = '';
    if (is_int($url)) {
        $id = $url;
    } elseif (preg_match('#(\d+)/?$#', $url, $matches)) {
        $id = $matches[1];
    }

    return $id;
}

function cleanStr($str)
{
    $tmpStr = "{\"text\": \"{$str}\"}";

    return json_decode($tmpStr)->text;
}

function getSDLink($curl_content)
{
    $regexRateLimit = '/playable_url":"([^"]+)"/';

    if (preg_match($regexRateLimit, $curl_content, $match)) {
        return cleanStr($match[1]);
    } else {
        return false;
    }
}

function getHDLink($curl_content)
{
    $regexRateLimit = '/playable_url_quality_hd":"([^"]+)"/';

    if (preg_match($regexRateLimit, $curl_content, $match)) {
        return cleanStr($match[1]);
    } else {
        return false;
    }
}

function getTitle($curl_content)
{
    $title = null;
    if (preg_match('/<title>(.*?)<\/title>/', $curl_content, $matches)) {
        $title = $matches[1];
    } elseif (preg_match('/title id="pageTitle">(.+?)<\/title>/', $curl_content, $matches)) {
        $title = $matches[1];
    }

    return cleanStr($title);
}

function getDescription($curl_content)
{
    if (preg_match('/span class="hasCaption">(.+?)<\/span>/', $curl_content, $matches)) {
        return cleanStr($matches[1]);
    }

    return false;
}
