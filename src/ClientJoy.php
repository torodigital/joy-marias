<?php

namespace ToroDigital\Joy;

use Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use stdClass;

class ClientJoy
{
    private static $host;
    private static $grant_type;
    private static $client_id;
    private static $client_secret;
    private static $scope;

    private static $cookies;
    private static Client $client;

    private static $authentication = '/oauth/token';
    private static $access_token = null;

    private static $endPointRankingGeneral = 'api/v1/ranking-general';
    private static $endPointRankingBlock = 'api/v1/ranking-block';

    public static function init()
    {
        self::$host = env('JOY_HOST');
        self::$grant_type = env('JOY_GRANT_TYPE');
        self::$client_id = env('JOY_CLIENT_ID');
        self::$client_secret = env('JOY_CLIENT_SECRET');
        self::$scope = env('JOY_SCOPE');

        self::$client = new Client([
            'base_uri' => self::$host,
            'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

        self::authentication();
    }

    public static function authentication()
    {
        try{
            $res = self::$client->post(self::$authentication, [
                'form_params' => [
                    'grant_type' => self::$grant_type,
                    'client_id' => self::$client_id,
                    'client_secret' => self::$client_secret,
                    'scope' => self::$scope,
                ]
            ]);

            self::$access_token = self::jsonp_decode($res->getBody())->access_token;

        }catch(Exception $e){
            return ['data' => [], 'pages' => 0, 'current_page' => 0, 'total' => 0];
        }
    }

    public static function response($res)
    {
        return json_decode($res->getBody());
    }

    public static function jsonp_decode($jsonp, $assoc = false) {
        $jsonp = (string)$jsonp;
        if($jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $jsonp = substr($jsonp, strpos($jsonp, '('));
        }
        $jsonp = trim($jsonp);      // remove trailing newlines
        $jsonp = trim($jsonp,'()'); // remove leading and trailing parenthesis

        return json_decode($jsonp, $assoc);
    }


    public static function getRankingGeneral($page = 1, $perPage = 10){

        try{
            $res = self::$client->get(self::$endPointRankingGeneral.'?page='.$page.'&perPage='.$perPage, [ 'headers' => [
                    'Authorization' => "Bearer ".self::$access_token
                ]
            ]);

            $res = self::jsonp_decode($res->getBody());

            $data = new stdClass;
            $data->data = $blocks->data->blocks;
            $data->next_page = $blocks->data->next_page;
            $data->prev_page = $blocks->data->prev_page;
            $data->page = $blocks->data->pages;

            return $data;

        }catch(ServerException $se){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(ClientException $clientException){
            // dd(Psr7\Message::toString(($clientException->getResponse())));
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(ConnectException $ce){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(Exception $e){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }
    }

    public static function getRankingBlock($block = 1, $page = 1, $perPage = 10){
        try{
            $res = self::$client->get(self::$endPointRankingBlock.'?block='.$block.'&page='.$page.'&perPage='.$perPage, [ 'headers' => [
                    'Authorization' => "Bearer ".self::$access_token
                ]
            ]);

            $res = json_decode($res->getBody());

            $data = new stdClass;
            $data->data = $blocks->data->blocks;
            $data->next_page = $blocks->data->next_page;
            $data->prev_page = $blocks->data->prev_page;
            $data->page = $blocks->data->pages;

            return $data;

        }catch(ServerException $se){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(ClientException $clientException){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(ConnectException $ce){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }catch(Exception $e){
            return ['data' => [], 'pages' => 0, 'next_page' => 0, 'prev_page' => 0];
        }
    }

    public static function pagination($links){
        $links = Collection::make($links);

        $points = $links->map(function($item, $key){
            return $item->label == "...";
        })->reject(function($i){
            return $i == false;
        });

        $keys = $points->keys();

        $b = $links->countBy(function($l){
            return $l->url == null;
        });

        if($points->count() == 2){
            $links->splice(0, $keys[0] + 1);
            $keys[1] = $keys[1] - ($keys[0] + 1);

            $links->splice($keys[1], $links->count());
        }
        else if($points->count() == 1){
            $links->splice($keys[0], $links->count());
            $links->splice(0, 1);
        }else if($b->count() == 2){
            $links->splice(0, 1);
            $links->splice(-1, 1);
        }

        return $links;
    }

}
