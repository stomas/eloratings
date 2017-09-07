<?php namespace Stomas\EloRanking;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use League\Csv\Reader;
use Stomas\Footballdataparser\Models\Team;

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
        if(Cache::has($team)) {
            $team = Cache::get($team);
        } else {
            $teamTemp = Team::search($team)->get();

            if(count($teamTemp) > 0){
                Cache::forever($team, $teamTemp->first());
            }

            $team = Cache::get($team);
        }
        if($team){
            return $team->elorating;
        }

        return 0;
    }

    public static function getTeamsFromSearches($matchName){
        $teams = explode(" v ", $matchName);

        if(Cache::has(trim($teams[0]))){
            $homeTeam = Cache::get(trim($teams[0]));
        } else {
            $homeTeam = Team::search(trim($teams[0]))->get();

            Cache::forever(trim($teams[0]), $homeTeam->first());

            $homeTeam = Cache::get(trim($teams[0]));

        }

        if(Cache::has(trim($teams[1]))){
            $awayTeam = Cache::get(trim($teams[1]));
        } else {
            $awayTeam = Team::search(trim($teams[1]))->get();

            Cache::forever(trim($teams[1]), $awayTeam->first());

            $awayTeam = Cache::get(trim($teams[1]));
        }

        return $homeTeam->team . ' v ' . $awayTeam->team;
    }

    public static function getEloDifferenceFromMatchName($mathcName){
        $teams = explode(" v ", $mathcName);

        if(count($teams) == 2){
            $homeTeam = self::getElo(trim($teams[0]));
            $awayTeam = self::getElo(trim($teams[1]));

            if($homeTeam > 0 && $awayTeam > 0){
                return $homeTeam - $awayTeam;
            }
        }

        return 0;
    }

    public static function getEloSystemForMatch($matchName){
        return 1 / (pow(10, -(self::getEloDifferenceFromMatchName($matchName)/400)) + 1);
    }

    public static function getMySystemForMatch($matchName){
        $teams = explode(" v ", $matchName);

        if(count($teams) == 2){
            $homeTeam = self::getElo(trim($teams[0]));
            $awayTeam = self::getElo(trim($teams[1]));

            if($homeTeam > 0 && $awayTeam > 0){
                return 1 / (pow(10, -(self::getEloDifferenceFromMatchName($matchName)/400)) + 1) * $homeTeam / $awayTeam;
            }
        }
    }

    public static function ELODIfference($match)
    {
        return $match->HomeTeamELO - $match->AwayTeamELO;
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

        if(count($filteredResults) == 0){
            return 0;
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