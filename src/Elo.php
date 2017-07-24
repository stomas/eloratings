<?php namespace Stomas\EloRanking;

use DateTime;
use GuzzleHttp\Client;
use League\Csv\Reader;

/**
 * Class Elo
 *
 * @package Stomas\EloRanking
 */
class Elo
{

    /**
     * @var string
     */
    static $url = 'http://api.clubelo.com/';
    /**
     * @var string
     */
    static $year = '';
    /**
     * @var string
     */
    static $team = '';

    /**
     * @param      $team
     * @param null $year
     *
     * @return float
     */
    public static function getElo($team, $year = null)
    {

        self::initYear($year);
        self::initTeam($team);

        $filtered_array = self::getFilteredResults();

        $average = self::getAverageElo($filtered_array);

        return $average;
    }

    /**
     * @return array
     */
    private static function getFilteredResults()
    {

        $client = new Client();

        $res = $client->request('GET', self::$url . self::$team);

        $csv = Reader::createFromString((string) $res->getBody());

        return array_filter($csv->fetchAll(), function ($var) {

            $date1 = new DateTime();
            $date1->setTimestamp(strtotime($var[5]));

            if($date1->format('Y') === self::$year) {
                return true;
            }

            return false;
        });
    }

    /**
     * @param $filteredResults
     *
     * @return float
     */
    private static function getAverageElo($filteredResults)
    {

        $sum = 0;
        foreach($filteredResults as $eloRating) {
            $sum += (float) $eloRating[4];
        }

        return $sum / count($filteredResults);
    }

    /**
     * @param $team
     */
    private static function initTeam($team)
    {

        self::$team = $team;
    }

    /**
     * @param $year
     */
    private static function initYear($year)
    {

        if(!$year) {
            self::$year = (new DateTime())->format('Y');
        } else {
            self::$year = $year;
        }
    }

}