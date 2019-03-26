<?php
/**
 * Simple OpenDota API library for PHP
 * API version: 17.6.1
 *
 * It's quite simple implemetation, that you can use by just requiring it
 * from your code.
 *
 * Current version doesn't have request queue and "holds" request instead.
 *
 * Every method represents specific OpenDota API endpoint.
 * OpenDota Docs: https://docs.opendota.com/
 *
 * Every API method has $mode parameter:
 * $mode = 0: Safe mode, sleep until API is ready (default)
 *       = 1: Force mode, don't wait for API cooldown
 *       =-1: Fast mode, drop request if API isn't ready
 *
 * For functions with `$param` argument see OpenDota docs to see parameters
 * that can be used for the endpoint.
 */

namespace SimpleOpenDotaPHP;

class odota_api {
  private $hostname;
  private $ready = \true;
  private $api_cooldown;
  private $last_request = 0;
  private $report_status;

  function __construct($cli_report_status=\false, $hostname="", $cooldown=0, $api_key="") {
    /**
     * $hostname = URL of API instance. Uses public OpenDota instance by default.
     * $cooldown = API cooldown, 1000ms/200ms by default (recommended by OpenDota docs).
     */

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

  # Inner class functions

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

    if ( $this->report_status ) {
      if ( !\curl_errno($curl) )
        echo("OK\n");
      else
        echo("\n[E] OpenDotaPHP: cURL error: ".\curl_error($curl)."\n");
    }

    \curl_close($curl);

    return $response;
  }

  private function cooldown() {
    if ( ($ms_timestamp = \microtime(\true)) - $this->last_request < $this->api_cooldown) {
      if ( $this->report_status )
        echo("...Holding On");

      \usleep( (int)(($ms_timestamp - $this->last_request) * 1000000) );
    }
    $this->ready = \true;
  }

  private function set_last_request() {
    $this->last_request = \microtime(\true);
    $this->ready = \false;
  }

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

  # ********** Matches

  public function match($match_id, $mode = 0) {
    # GET /matches/{match_id}
    # Returns match data
    #
    # $match_id = {match_id}
    $match_id = $match_id;
    return $this->request("matches/".$match_id, $mode);
  }

  # ********** Players

  public function player($player_id, $mode = 0) {
    # GET /players/{account_id}
    # $player_id (int) = {account_id}
    # Returns player data

    return $this->request("players/".$player_id, $mode);
  }

  public function player_winloss($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/wl
    # Returns player's win/loss count
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/wl", $mode, $params);
  }

  public function player_recent_matches($player_id, $mode = 0) {
    # GET /players/{account_id}/recentMatches
    # Returns player's recent matches played
    #
    # $player_id (int) = {account_id}

    return $this->request("players/".$player_id."/recentMatches", $mode);
  }

  public function player_matches($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/matches
    # Returns player's matches played
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/matches", $mode, $params);
  }

  public function player_heroes($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/heroes
    # Returns player's heroes played
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/heroes", $mode, $params);
  }

  public function player_peers($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/peers
    # Returns list of players a player played with
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/peers", $mode, $params);
  }

  public function player_pros($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/pros
    # Returns list of pro players a player played with
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/pros", $mode, $params);
  }

  public function player_totals($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/totals
    # Returns player's totals in stats
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/totals", $mode, $params);
  }

  public function player_counts($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/counts
    # Returns player's counts in categories
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/counts", $mode, $params);
  }

  public function player_histograms($player_id, $field = "", $params = [], $mode = 0) {
    # GET /players/{account_id}/histograms
    # Returns player's distribution in a single stat
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters
    #
    # if $field isn't empty:
    # GET /players/{account_id}/histograms/{field}
    # $field = {field} - a field to aggregate on


    if ( empty($field) )
      return $this->request("players/".$player_id."/histograms", $mode, $params);
    else
      return $this->request("players/".$player_id."/histograms/".$field, $mode, $params);
  }

  public function player_wardmap($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/wardmap
    # Returns player's wards placed in matches played
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/wardmap", $mode, $params);
  }

  public function player_wordcloud($player_id, $params = [], $mode = 0) {
    # GET /players/{account_id}/wordcloud
    # Returns player's words said/read in matches played
    #
    # $player_id (int) = {account_id}
    #
    # Has additional parameters

    return $this->request("players/".$player_id."/wordcloud", $mode, $params);
  }

  public function player_ratings($player_id, $mode = 0) {
    # GET /players/{account_id}/ratings
    # Returns player's rating history
    #
    # $player_id (int) = {account_id}

    return $this->request("players/".$player_id."/ratings", $mode);
  }

  public function player_rankings($player_id, $mode = 0) {
    # GET /players/{account_id}/rankings
    # Returns player's hero rankings
    #
    # $player_id (int) = {account_id}

    return $this->request("players/".$player_id."/rankings", $mode);
  }

  public function player_refresh($player_id, $mode = 0) {
    # POST /players/{account_id}/refresh
    # Refresh player's match history
    #
    # $player_id (int) = {account_id}

    return $this->request("players/".$player_id."/refresh", $mode, [], \true);
  }

  # ********** Pro Players

  public function pro_players($mode = 0) {
    # GET /proPlayers
    # Get list of pro players
    return $this->request("proPlayers", $mode);
  }

  # ********** Pro Matches

  public function pro_matches($less_than_match_id = null, $mode = 0) {
    # GET /proMatches
    # Get list of pro matches
    #
    # Has additional parameter:
    #    $less_than_match_id (int) = less_than_match_id :
    #        Get matches with a match ID lower than this value
    #           null = not set (default)
    if ( $less_than_match_id === null )
      return $this->request("proMatches", $mode);
    else
      return $this->request("proMatches", $mode, [ "less_than_match_id" => (int)$less_than_match_id ]);
  }

  # ********** Public Matches

  public function public_matches($less_than_match_id = null, $sort = 0, $mode = 0) {
    # GET /publicMatches
    # Get list of randomly sampled public matches
    #
    # Has additional parameters:
    #    $less_than_match_id (int) = less_than_match_id :
    #        Get matches with a match ID lower than this value
    #           null = not set (default)
    #    $sort (int) :
    #        1 : Sort matches ascending (sets mmr_ascending parameter in request)
    #       -1 : Sort matches descending (sets mmr_ascending parameter in request)
    #        0 : No sort (doesn't set any parameters)

    $params = [];

    if ( $less_than_match_id !== null )
      $params['less_than_match_id'] = (int)$less_than_match_id;

    if ( $sort == 1 )
      $params['mmr_ascending'] = "";
    else if ( $sort == -1 )
      $params['mmr_descending'] = "";

    return $this->request("publicMatches", $mode, $params);
  }

  # ********* Explorer

  public function explorer($request, $mode = 0) {
    # GET /explorer
    # Submit arbitrary SQL queries to the database
    #
    # Has additional parameter:
    #    $request (string) = sql : PostgreSQL query

    if ( empty($request) )
      return \false;

    return $this->request("explorer", $mode, ["sql" => $request]);
  }

  # ********* Metadata

  public function metadata($mode = 0) {
    # GET /metadata
    # Site metadata
    return $this->request("metadata", $mode);
  }

  # ********* Distributions

  public function distributions($mode = 0) {
    # GET /distributions
    # Distributions of MMR data by bracket and country
    return $this->request("distributions", $mode);
  }

  # ********* Search

  public function search($request, $similarity=0.51, $mode = 0) {
    # GET /search
    # Search players by personaname
    #
    # Has additional parameters:
    #    $request (string) = q : Search String (required)
    #    $similarity (float) = similarity : Minimum similarity treshold,
    #       between 0 and 1. Default: 0.51

    $params = [];

    if ( empty($request) )
      return \false;

    if ( $similarity != 0.51 )
      $params['similarity'] = (float) $similarity;

    $params['q'] = $request;

    return $this->request("search", $mode, $params);
  }

  # ********** Rankings

  public function rankings($hero_id, $mode = 0) {
    # GET /rankings
    # Get top players by hero
    #
    # $hero_id (int) = hero_id : Hero ID
    $hero_id = (int)$hero_id;
    return $this->request("rankings", $mode, ["hero_id" => $hero_id]);
  }

  # ********** Benchmarks

  public function benchmarks($hero_id, $mode = 0) {
    # GET /benchmarks
    # Benchmarks of average stat values for a hero
    #
    # $hero_id (int) = hero_id : Hero ID

    if ( !isset($hero_id) )
      return \false;
    $hero_id = (int)$hero_id;

    return $this->request("benchmarks", $mode, ["hero_id" => $hero_id]);
  }

  # ********** Status

  public function status($mode = 0) {
    # GET /status
    # Get current service statistics
    return $this->request("status", $mode);
  }

  # ********** Health

  public function health($mode = 0) {
    # GET /health
    # Get service health data
    return $this->request("health", $mode);
  }

  # ********** Request

  public function request_status($job_id = "", $mode = 0) {
    # GET /request/{jobid}
    # Get parse request state
    #
    # $job_id (string) = {jobid} - The job ID to query.

    if ( empty($job_id) )
      return \false;

    return $this->request("request/".$job_id, $mode);
  }

  public function request_match($match_id, $mode = 0) {
    # POST /request/{match_id}
    # Submit a new parse request
    #
    # $match_id = {match_id}

    return $this->request("request/".$match_id, $mode, [], \true);
  }

  # ********** Heroes

  public function heroes_metadata($mode = 0) {
    # GET /heroes
    # Get hero data

    return $this->request("heroes", $mode);
  }

  public function hero_matches($hero_id, $mode = 0) {
    # GET /heroes/{hero_id}/matches
    # Get recent matches with a hero
    #
    # $hero_id (int) = {hero_id} - Hero ID
    $hero_id = (int)$hero_id;
    return $this->request("heroes/".$hero_id."/matches", $mode);
  }

  public function hero_matchups($hero_id, $mode = 0) {
    # GET /heroes/{hero_id}/matchups
    # Get results against other heroes for a hero
    #
    # $hero_id (int) = {hero_id} - Hero ID
    $hero_id = (int)$hero_id;
    return $this->request("heroes/".$hero_id."/matchups", $mode);
  }

  public function hero_durations($hero_id, $mode = 0) {
    # GET /heroes/{hero_id}/durations
    # Get hero performance over a range of match durations
    #
    # $hero_id (int) = {hero_id} - Hero ID
    $hero_id = (int)$hero_id;
    return $this->request("heroes/".$hero_id."/durations", $mode);
  }

  public function hero_players($hero_id, $mode = 0) {
    # GET /heroes/{hero_id}/players
    # Get players who have played this hero
    #
    # $hero_id (int) = {hero_id} - Hero ID
    $hero_id = (int)$hero_id;
    return $this->request("heroes/".$hero_id."/players", $mode);
  }

  # ********** Hero Stats

  public function heroes_stats($mode = 0) {
    # GET /heroStats
    # Get stats about hero performance in recent matches
    return $this->request("heroStats", $mode);
  }

  # ********** Leagues

  public function leagues($mode = 0) {
    # GET /leagues
    # Get league data
    return $this->request("leagues", $mode);
  }

  # ********** Teams

  public function teams($mode = 0) {
    # GET /teams
    # Get team data
    return $this->request("teams", $mode);
  }

  public function team($team_id, $mode = 0) {
    # GET /teams/{team_id}
    # Get data for a team
    #
    # $team_id (int) = {team_id} - Team ID
    $team_id = (int)$team_id;
    return $this->request("teams/".$team_id, $mode);
  }

  public function team_matches($team_id, $mode = 0) {
    # GET /teams/{team_id}/matches
    # Get matches for a team
    #
    # $team_id (int) = {team_id} - Team ID
    $team_id = (int)$team_id;
    return $this->request("teams/".$team_id."/matches", $mode);
  }

  public function team_players($team_id, $mode = 0) {
    # GET /teams/{team_id}/players
    # Get players who have played for a team
    #
    # $team_id (int) = {team_id} - Team ID
    $team_id = (int)$team_id;
    return $this->request("teams/".$team_id."/players", $mode);
  }

  public function team_heroes($team_id, $mode = 0) {
    # GET /teams/{team_id}/heroes
    # Get heroes for a team
    #
    # $team_id (int) = {team_id} - Team ID
    $team_id = (int)$team_id;
    return $this->request("teams/".$team_id."/heroes", $mode);
  }

  # ********** Replays

  public function replay($match_id, $mode = 0) {
    # GET /replays
    # Get data to construct a replay URL with
    #
    # $match_id (int/array) = match_id - Match IDs
    return $this->request("replays", $mode, [ "match_id" => $match_id ]);
  }

  # ********** Records

  public function records($field, $mode = 0) {
    # GET /records/{field}
    # Get top performances in a stat
    #
    # $field (string) = {field} - Field name to query
    return $this->request("replays/".$field, $mode);
  }

  # ********** Live

  public function live($mode = 0) {
    # GET /live
    # Get top currently ongoing live games
    return $this->request("live", $mode);
  }

  # ********** Scenarios

  public function scenarios_item_timings($item, $hero_id, $mode = 0) {
    # GET /scenarios/itemTimings
    # Win rates for certain item timings on a hero for items that cost at least 1400 gold
    #
    # $item (string) = item - Filter by item name e.g. "spirit_vessel"
    # $hero_id (int) = hero_id - Hero ID
    return $this->request("scenarios/itemTimings", $mode, [ "item" => $item, "hero_id" => $hero_id ]);
  }

  public function scenarios_lane_roles($lane_role, $hero_id, $mode = 0) {
    # GET /scenarios/laneRoles
    # Win rates for heroes in certain lane roles
    #
    # $lane_role (string) = lane_role - Filter by lane role 1-4 (Safe, Mid, Off, Jungle)
    # $hero_id (int) = hero_id - Hero ID
    return $this->request("scenarios/laneRoles", $mode, [ "lane_role" => $lane_role, "hero_id" => $hero_id ]);
  }

  public function scenarios_misc($scenario, $mode = 0) {
    # GET /scenarios/misc
    # Miscellaneous team scenarios
    #
    # $scenario (string) = scenario - pos_chat_1min,neg_chat_1min,courier_kill,first_blood
    return $this->request("scenarios/misc", $mode, [ "scenario" => $scenario ]);
  }


  # ********** Schema

  public function schema($mode = 0) {
    # GET /schema
    # Get database schema
    return $this->request("schema", $mode);
  }

  # ********** Admin

  public function api_metrics($mode = 0) {
    # GET /admin/apiMetrics
    # Get API request metrics
    return $this->request("admin/apiMetrics", $mode);
  }

}

?>
