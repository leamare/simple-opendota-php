<?php
/**
 * Simple OpenDota API interface for PHP
 *
 * It's quite simple implemetation, that you can use by just requiring it
 * from your code.
 *
 * Current version doesn't have request queue and "holds" request instead.
 *
 * Every method represents specific OpenDota API endpoint.
 *
 * Every API method has $mode parameter:
 * $mode = 0: Safe mode, sleep until API is ready (default)
 *       = 1: Force mode, don't wait for API cooldown
 *       =-1: Fast mode, drop request if API isn't ready
 *
 * For functions with `$param` argument see OpenDota docs to see parameters
 * that can be used for the endpoint.
 * 
 * @version 17.7.0
 * @link https://docs.opendota.com/
 * @author Leamare
 */

 /** @package SimpleOpenDotaPHP */
namespace SimpleOpenDotaPHP;

/** @class OpenDota API interface */
class odota_api {
  /** @var string $hostname Currently active API address (api.opendota.com by default) */
  private $hostname;
  /** @var bool $ready API interface status */
  private $ready = \true;
  /** @var float $api_cooldown API cooldown time between requests */
  private $api_cooldown;
  /** @var float $last_request Last request time */
  private $last_request = 0;
  /** @var bool $report_status Verbose mode handler */
  private $report_status;

  /**
   * @param bool $cli_report_status = false Verbose mode flag
   * @param string $hostname = "" URL of API instance. Uses public OpenDota instance by default.
   * @param float  $cooldown = 0 API Cooldown 1000ms/200ms by default (recommended by OpenDota docs).
   * @param string $api_key = "" OpenDota API Key
   */
  function __construct($cli_report_status=\false, $hostname="", $cooldown=0, $api_key="") {
    if (!empty($hostname))
      $this->hostname = $hostname;
    else
      $this->hostname = "https://api.opendota.com/api/";

    $this->api_key = $api_key;

    if ($cooldown)
      $this->api_cooldown = $cooldown/1000;
    else if (!empty($this->api_key))
      $this->api_cooldown = 0.25;
    else
      $this->api_cooldown = 1;

    $this->report_status = $cli_report_status;

    if ( $this->report_status ) {
      echo("[I] OpenDotaPHP: Initialised OpenDota instance.\n[ ] \tHost: ".$this->hostname."\n");
    }
  }

  // Inner class functions

  /**
   * Execute GET request
   * 
   * @param string $url
   * @param mixed $data
   * 
   * @return $response
   */
  private function get($url, $data = []) {
    if (!empty($this->api_key))
      $data['api_key'] = $this->api_key;

    if (!empty($data)) {
      $url .= "?".\http_build_query($data);
    }

    $curl = \curl_init($this->hostname.$url);

    if ( $this->report_status ) {
      echo("...");
    }

    \curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, \false);
    \curl_setopt($curl, CURLOPT_RETURNTRANSFER, \true);

    $response = \curl_exec($curl);

    if ( \curl_errno($curl) )
      $response = \false;

    if ( $this->report_status ) {
      if ( !\curl_errno($curl) )
        echo("OK\n");
      else
        echo("\n[E] OpenDotaPHP: cURL error: ".\curl_error($curl)."\n");
    }

    \curl_close($curl);

    return $response;
  }

  /**
   * Execute POST request
   * 
   * @param string $url
   * @param mixed $data
   * 
   * @return string $response
   */
  private function post($url, $data = []) {
    if (!empty($this->api_key))
      $url .= "?api_key=".$this->api_key;

    $curl = \curl_init($this->hostname.$url);

    if ( $this->report_status ) {
      echo("...");
    }

    \curl_setopt($curl, CURLOPT_POST, \true);
    \curl_setopt($curl, CURLOPT_POSTFIELDS, \http_build_query($data));
    \curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, \false);
    \curl_setopt($curl, CURLOPT_RETURNTRANSFER, \true);

    $response = \curl_exec($curl);

    if ( \curl_errno($curl) )
      $response = \false;
    if ( strpos($response, "<!DOCTYPE HTML>") !== FALSE )
      $response = "{\"error\":\"Node disabled\"}";

    if ( $this->report_status ) {
      if ( \curl_errno($curl) || !$response )
        echo("\n[E] OpenDotaPHP: cURL error: ".\curl_error($curl)."\n");
      else 
        echo("OK\n");
    }

    \curl_close($curl);

    return $response;
  }

  /**
   * Handles API cooldown
   */
  private function cooldown() {
    // I shouldn't really use microtime here, but I will anyway
    // mostly because it's easier to read this way
    $ms_timestamp = \microtime(\true) - $this->last_request;
    if ( $ms_timestamp < $this->api_cooldown) {
      if ( $this->report_status )
        echo("...Holding On");

      \usleep( (int)($this->api_cooldown - $ms_timestamp) * 1000000 );
    }
    $this->ready = \true;
  }

  /**
   * Handles last request time
   */
  private function set_last_request() {
    $this->last_request = \microtime(\true);
    $this->ready = \false;
  }

  /**
   * Handles requests and resulting data
   * 
   * @param string $url
   * @param int $mode 
   * @param mixed $data 
   * @param bool $post
   * 
   * @return mixed $result
   */
  private function request($url, $mode, $data = [], $post = \false) {
    if ( $this->report_status )
      echo("[ ] OpenDotaPHP: Sending request to /$url endpoint");

    if ( $mode == 0 ) {
      $this->cooldown();
    } else if ( $mode == -1 ) {
      if ( !$this->ready ) {
        if ( $this->report_status ) {
          echo("[E] OpenDotaPHP: API Cooldown. Skipping request\n");
        }
      }
    }

    if($post === \false) {
      $result = $this->get($url, $data);
    } else {
      $result = $this->post($url, $data);
    }

    $this->set_last_request();

    $result = \json_decode($result, \true);

    if(isset($result['error']) || empty($result)) {
        if ( $mode == -1 ) {
            if ( $this->report_status )
                echo("[E] OpenDotaPHP: ".$result['error'].". Skipping request\n");
            return \false;
        } else if ( $result['error'] == "Not Found" ) {
            if ( $this->report_status )
                echo("[ ] OpenDotaPHP: 404, Skipping\n");
            return \false;
        } else if ( $result['error'] == "Node disabled" ) {
            if ( $this->report_status )
                echo("[ ] OpenDotaPHP: Node disabled\n");
            return \false;
        } if ( $mode == 0 ) {
            if ( $this->report_status )
                echo("[ ] OpenDotaPHP: ".$result['error'].". Waiting\n");
            \sleep(1);
            return $this->request($url, $mode, $data, $post);
        }
    } else {
        return $result;
    }
  }

  // ********** Matches

  /**
   * GET /matches/{match_id}
   * Returns match data
   * 
   * @param string $match_id {match_id}
   * @param int $mode = 0
   * 
   * @return mixed $result Match data blob
   */
  public function match($match_id, $mode = 0) {
    return $this->request("matches/".$match_id, $mode);
  }

  // ********** Players By Rank

  /**
   * GET /playersByRank
   * Players ordered by rank/medal tier
   * 
   * @return mixed $result List of players [ account_id, rank_tier, fh_unavailable ]
   */
  public function playersByRank($mode = 0) {
    return $this->request("playersByRank", $mode);
  }

  // ********** Players

  /**
   * GET /players/{account_id}
   * Returns player data
   * 
   * @param int $player_id {account_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Player data blob
   */
  public function player($player_id, $mode = 0) {
    return $this->request("players/".$player_id, $mode);
  }

  /**
   * GET /players/{account_id}/wl
   * Returns player's win/loss count
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied to filter matches (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * @return mixed $result Win/loss data
   */
  public function player_winloss($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/wl", $mode, $params);
  }

  /**
   * GET /players/{account_id}/recentMatches
   * Returns player's recent matches played
   * 
   * @param int $player_id {account_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Object of recent matches played
   */
  public function player_recent_matches($player_id, $mode = 0) {
    return $this->request("players/".$player_id."/recentMatches", $mode);
  }

  /**
   * GET /players/{account_id}/matches
   * Returns player's matches played
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Object of matches played filtered by $params
   */
  public function player_matches($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/matches", $mode, $params);
  }

  /**
   * GET /players/{account_id}/heroes
   * Returns player's heroes played
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Object of player's heroes played data
   */
  public function player_heroes($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/heroes", $mode, $params);
  }

  /**
   * GET /players/{account_id}/peers
   * Returns list of players a player played with
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Object of player's peers data
   */
  public function player_peers($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/peers", $mode, $params);
  }

  /**
   * GET /players/{account_id}/pros
   * Returns list of pro players a player played with
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Object of player's matchups with pros
   */
  public function player_pros($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/pros", $mode, $params);
  }

  /**
   * GET /players/{account_id}/totals
   * Returns player's totals in stats
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Total stats object
   */
  public function player_totals($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/totals", $mode, $params);
  }

  /**
   * GET /players/{account_id}/counts
   * Returns player's counts in categories
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Object of player's counts
   */
  public function player_counts($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/counts", $mode, $params);
  }

  /**
   * GET /players/{account_id}/histograms/{field = ""}
   * Returns player's distribution in a single stat
   * 
   * @param int $player_id {account_id}
   * @param string $field = "" A field to aggregate on
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Player's distributions object
   */
  public function player_histograms($player_id, $field = "", $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/histograms".(empty($field) ?: "/").$field, $mode, $params);
  }

  /**
   * GET /players/{account_id}/wardmap
   * Returns player's wards placed in matches played
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Player's wards object
   */
  public function player_wardmap($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/wardmap", $mode, $params);
  }

  /**
   * GET /players/{account_id}/wordcloud
   * Returns player's words said/read in matches played
   * 
   * @param int $player_id {account_id}
   * @param mixed $params = [] Assoc array of parameters applied (see OpenDota docs for reference)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Wordcloud object
   */
  public function player_wordcloud($player_id, $params = [], $mode = 0) {
    return $this->request("players/".$player_id."/wordcloud", $mode, $params);
  }

  /**
   * GET /players/{account_id}/ratings
   * Returns player's rating history
   * 
   * @param int $player_id {account_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function player_ratings($player_id, $mode = 0) {
    return $this->request("players/".$player_id."/ratings", $mode);
  }

  /**
   * GET /players/{account_id}/rankings
   * Returns player's hero rankings
   * 
   * @param int $player_id {account_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function player_rankings($player_id, $mode = 0) {
    return $this->request("players/".$player_id."/rankings", $mode);
  }

  /**
   * GET /players/{account_id}/refresh
   * Refresh player's match history
   * 
   * @param int $player_id {account_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function player_refresh($player_id, $mode = 0) {
    return $this->request("players/".$player_id."/refresh", $mode, [], \true);
  }

  // ********** Pro Players

  /**
   * GET /proPlayers
   * Get list of pro players
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function pro_players($mode = 0) {
    return $this->request("proPlayers", $mode);
  }

  // ********** Pro Matches

  /**
   * GET /proMatches
   * Get list of pro matches
   * 
   * @param int $less_than_match_id = null Get matches with a match ID lower than this value
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function pro_matches($less_than_match_id = null, $mode = 0) {
    if ( $less_than_match_id === null )
      return $this->request("proMatches", $mode);
    else
      return $this->request("proMatches", $mode, [ "less_than_match_id" => (int)$less_than_match_id ]);
  }

  // ********** Public Matches

  /** 
   * GET /publicMatches
   * Get list of randomly sampled public matches
   * 
   * @param int $less_than_match_id = null Get matches with a match ID lower than this value
   * @param int $sort = 0 Sort order (0 = no sort, 1 = ascending, -1 = descending)
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function public_matches($less_than_match_id = null, $sort = 0, $mode = 0) {
    $params = [];

    if ( $less_than_match_id !== null )
      $params['less_than_match_id'] = (int)$less_than_match_id;

    if ( $sort == 1 )
      $params['mmr_ascending'] = "";
    else if ( $sort == -1 )
      $params['mmr_descending'] = "";

    return $this->request("publicMatches", $mode, $params);
  }

  // ********** Parsed Matches

  /** 
   * GET /parsedMatches
   * Get list of randomly sampled public matches
   * 
   * @param int $less_than_match_id = null Get matches with a match ID lower than this value
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function parsed_matches($less_than_match_id = null, $mode = 0) {
    $params = [];

    if ( $less_than_match_id !== null )
      $params['less_than_match_id'] = (int)$less_than_match_id;

    return $this->request("parsedMatches", $mode, $params);
  }

  // ********* Explorer

  /**
   * GET /explorer
   * Submit arbitrary SQL queries to the database
   * 
   * @param string $request PostgreSQL query
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function explorer($request, $mode = 0) {
    if ( empty($request) )
      return \false;

    return $this->request("explorer", $mode, ["sql" => $request]);
  }

  // ********* Metadata

  /**
   * GET /metadata
   * Site metadata
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function metadata($mode = 0) {
    return $this->request("metadata", $mode);
  }

  // ********* Distributions

  /**
   * GET /distributions
   * Distributions of MMR data by bracket and country
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function distributions($mode = 0) {
    return $this->request("distributions", $mode);
  }

  // ********* Search

  /**
   * GET /search
   * Search players by personaname
   * 
   * @param string $request Search query string
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function search($request, $mode = 0) {
    $params = [];

    if ( empty($request) )
      return \false;

    $params['q'] = $request;

    return $this->request("search", $mode, $params);
  }

  // ********** Rankings

  /**
   * GET /rankings
   * Get top players by hero
   * 
   * @param int $hero_id
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function rankings($hero_id, $mode = 0) {
    return $this->request("rankings", $mode, ["hero_id" => (int)$hero_id]);
  }

  // ********** Benchmarks

  /**
   * GET /benchmarks
   * Benchmarks of average stat values for a hero
   * 
   * @param int $hero_id
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function benchmarks($hero_id, $mode = 0) {
    if ( !isset($hero_id) )
      return \false;

    return $this->request("benchmarks", $mode, ["hero_id" => (int)$hero_id]);
  }

  // ********** Status

  /**
   * GET /status
   * Get current service statistics
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function status($mode = 0) {
    return $this->request("status", $mode);
  }

  // ********** Health

  /**
   * GET /health
   * Get service health data
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function health($mode = 0) {
    return $this->request("health", $mode);
  }

  // ********** Request

  /**
   * GET /request/{jobid}
   * Get parse request state
   * 
   * @param string $job_id {jobid} The job ID to query.
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function request_status($job_id, $mode = 0) {
    return $this->request("request/".$job_id, $mode);
  }

  /**
   * POST /request/{match_id}
   * Submit a new parse request
   * 
   * @param string $match_id {match_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function request_match($match_id, $mode = 0) {
    return $this->request("request/".$match_id, $mode, [], \true);
  }

  // ********** Heroes

  /**
   * GET /heroes
   * Get hero data
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function heroes_metadata($mode = 0) {
    return $this->request("heroes", $mode);
  }

  /**
   * GET /heroes/{hero_id}/matches
   * Get recent matches with a hero
   * 
   * @param int $hero_id {hero_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function hero_matches($hero_id, $mode = 0) {
    return $this->request("heroes/".((int)$hero_id)."/matches", $mode);
  }

  /**
   * GET /heroes/{hero_id}/matchups
   * Get results against other heroes for a hero
   * 
   * @param int $hero_id {hero_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function hero_matchups($hero_id, $mode = 0) {
    return $this->request("heroes/".((int)$hero_id)."/matchups", $mode);
  }

  /**
   * GET /heroes/{hero_id}/durations
   * Get hero performance over a range of match durations
   * 
   * @param int $hero_id {hero_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function hero_durations($hero_id, $mode = 0) {
    $hero_id = (int)$hero_id;
    return $this->request("heroes/".((int)$hero_id)."/durations", $mode);
  }

  /**
   * GET /heroes/{hero_id}/players
   * Get players who have played this hero
   * 
   * @param int $hero_id {hero_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function hero_players($hero_id, $mode = 0) {
    $hero_id = (int)$hero_id;
    return $this->request("heroes/".((int)$hero_id)."/players", $mode);
  }

  // ********** Hero Stats

  /**
   * GET /heroStats
   * Get stats about hero performance in recent matches
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function heroes_stats($mode = 0) {
    return $this->request("heroStats", $mode);
  }

  // ********** Leagues

  /**
   * GET /leagues
   * Get league data
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function leagues($mode = 0) {
    return $this->request("leagues", $mode);
  }

  // ********** Teams

  /**
   * GET /teams
   * Get team data
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function teams($mode = 0) {
    return $this->request("teams", $mode);
  }

  /**
   * GET /teams/{team_id}
   * Get data for a team
   * 
   * @param int $team_id {team_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result Team data blob
   */
  public function team($team_id, $mode = 0) {
    return $this->request("teams/".((int)$team_id), $mode);
  }

  /**
   * GET /teams/{team_id}/matches
   * Get matches for a team
   * 
   * @param int $team_id {team_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function team_matches($team_id, $mode = 0) {
    return $this->request("teams/".((int)$team_id)."/matches", $mode);
  }

  /**
   * GET /teams/{team_id}/players
   * Get players who have played for a team
   * 
   * @param int $team_id {team_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function team_players($team_id, $mode = 0) {
    return $this->request("teams/".((int)$team_id)."/players", $mode);
  }

  /**
   * GET /teams/{team_id}/heroes
   * Get heroes for a team
   * 
   * @param int $team_id {team_id}
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function team_heroes($team_id, $mode = 0) {
    return $this->request("teams/".((int)$team_id)."/heroes", $mode);
  }

  // ********** Replays

  /**
   * GET /replays
   * Get data to construct a replay URL with
   * 
   * @param mixed $match_id {match_id} string/int/array of match IDs
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function replay($match_id, $mode = 0) {
    return $this->request("replays", $mode, [ "match_id" => $match_id ]);
  }

  // ********** Records

  /**
   * GET /records/{field}
   * Get top performances in a stat
   * 
   * @param string $field {match_id} Field name to query
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function records($field, $mode = 0) {
    return $this->request("replays/".$field, $mode);
  }

  // ********** Live

  /**
   * GET /live
   * Get top currently ongoing live games
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function live($mode = 0) {
    return $this->request("live", $mode);
  }

  // ********** Scenarios

  /**
   * GET /scenarios/itemTimings
   * Win rates for certain item timings on a hero for items that cost at least 1400 gold
   * 
   * @param string $item Filter by item name e.g. "spirit_vessel"
   * @param int $hero_id 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function scenarios_item_timings($item, $hero_id, $mode = 0) {
    return $this->request("scenarios/itemTimings", $mode, [ "item" => $item, "hero_id" => (int)$hero_id ]);
  }

  /**
   * GET /scenarios/laneRoles
   * Win rates for heroes in certain lane roles
   * 
   * @param int $item Filter by lane role 1-4 (Safe, Mid, Off, Jungle)
   * @param int $hero_id 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function scenarios_lane_roles($lane_role, $hero_id, $mode = 0) {
    return $this->request("scenarios/laneRoles", $mode, [ "lane_role" => (int)$lane_role, "hero_id" => (int)$hero_id ]);
  }

  /**
   * GET /scenarios/misc
   * Miscellaneous team scenarios
   * 
   * @param string $scenario pos_chat_1min,neg_chat_1min,courier_kill,first_blood
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function scenarios_misc($scenario, $mode = 0) {
    return $this->request("scenarios/misc", $mode, [ "scenario" => $scenario ]);
  }


  // ********** Schema

  /**
   * GET /schema
   * Get database schema
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function schema($mode = 0) {
    return $this->request("schema", $mode);
  }

  // ********** Admin

  /**
   * GET /admin/apiMetrics
   * Get API request metrics
   * 
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function api_metrics($mode = 0) {
    return $this->request("admin/apiMetrics", $mode);
  }

  // ********** Find Matches

  /**
   * GET /findMatches
   * Get API request metrics
   * 
   * @param array $teams Array of Hero IDs
   * 
   * @return mixed $result
   */
  public function find_matches($teams, $mode = 0) {
    if(!is_array($teams)) return false;
    $teams = array_combine(["teamA", "teamB"], $teams);
    return $this->request("findMatches", $mode, $teams);
  }

  // ********** Constants

  /**
   * GET /constants
   * Get static game data mirrored from the dotaconstants repository
   * Resources: https://github.com/odota/dotaconstants/tree/master/build
   * 
   * @param string $resource Resource name e.g. heroes
   * @param int $mode = 0 Fast mode flag (skip requests if cooldown or wait for API)
   * 
   * @return mixed $result
   */
  public function constants($resource, $mode = 0) {
    return $this->request("constants/".$resource, $mode);
  }

  // ********** Feed

  /**
   * GET /feed
   * Get streaming feed of latest matches as newline-delimited JSON
   * Requires API key
   * *** May not work properly as for now ***
   * 
   * @param array $params = [] Array of filters
   *    available filters: seq_num, game_mode, leagueid, included_account_id
   * 
   * @return mixed $result
   */
  // public function feed($params = [], $mode = 0) {
  //   if(!is_array($params)) return false;
  //   if(empty($this->api_key)) return false;
  //   return $this->request("findMatches", $mode, $teams);
  // }
}

?>
