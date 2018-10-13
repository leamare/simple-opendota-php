### Note

Every function has unrequired parameter `$mode`, that's always placed after everything else. For more information on its usage check [README.md](README.md).

### Matches

API Endpoint | Function | Parameters
-- | -- | --
`GET /matches/{match_id}` | `match($match_id)` | `$match_id = {match_id}`

### Players

**Note:** `$params` list if parameters is listed in OpenDota docs and specifies filters that will be applied to player's stats.

API Endpoint | Function | Parameters
-- | -- | --
`GET /players/{account_id}` | `player($player_id)` | `$player_id (int) = {account_id}`
`GET /players/{account_id}/wl` | `player_winloss($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/recentMatches` | `player_recent_matches($player_id)` | `$player_id (int) = {account_id}`
`GET /players/{account_id}/matches` | `player_matches($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/heroes` | `player_heroes($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/peers` | `player_peers($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/pros` | `player_pros($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/totals` | `player_totals($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/counts` | `player_counts($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/histograms` | `player_pros($player_id [, $field, $params])` | `$player_id (int) = {account_id}`, `$params`, `$field = {field}` - a field to aggregate on
`GET /players/{account_id}/wardmap` | `player_wardmap($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/wordcloud` | `player_wordcloud($player_id [, $params])` | `$player_id (int) = {account_id}`, `$params`
`GET /players/{account_id}/ratings` | `player_ratings($player_id)` | `$player_id (int) = {account_id}`
`GET /players/{account_id}/rankings` | `player_rankings($player_id)` | `$player_id (int) = {account_id}`
`POST /players/{account_id}/refresh` | `player_refresh($player_id)` | `$player_id (int) = {account_id}`

### Pro Players

API Endpoint | Function | Parameters
-- | -- | --
`GET /proPlayers` | `pro_players()` | none

### Pro Matches

API Endpoint | Function | Parameters
-- | -- | --
`GET /proMatches` | `pro_matches([$less_than_match_id])` | `$less_than_match_id (int) = less_than_match_id` - Get matches with a match ID lower than this value; null = not set (default)

### Public Matches

API Endpoint | Function | Parameters
-- | -- | --
`GET /publicMatches` | `public_matches([$less_than_match_id])` | `$less_than_match_id (int) = less_than_match_id` - Get matches with a match ID lower than this value; null = not set (default)

### Public Matches

API Endpoint | Function | Parameters
-- | -- | --
`GET /explorer` | `explorer($request)` | `$request (string) = sql` : PostgreSQL query

### Metadata

API Endpoint | Function | Parameters
-- | -- | --
`GET /metadata` | `metadata()` | none

### Distributions

API Endpoint | Function | Parameters
-- | -- | --
`GET /distributions` | `distributions()` | none

### Search

API Endpoint | Function | Parameters
-- | -- | --
`GET /search` | `metadata($request[, $similarity])` | `$request (string) = q` - Search String (required); `$similarity (float) = similarity` - Minimum similarity treshold, between 0 and 1. Default: 0.51

### Rankings

API Endpoint | Function | Parameters
-- | -- | --
`GET /rankings` | `rankings($hero_id)` | `$hero_id (int) = hero_id`

### Benchmarks

API Endpoint | Function | Parameters
-- | -- | --
`GET /benchmarks` | `benchmarks($hero_id)` | `$hero_id (int) = hero_id`

### Status

API Endpoint | Function | Parameters
-- | -- | --
`GET /status` | `status()` | none

### Health

API Endpoint | Function | Parameters
-- | -- | --
`GET /health` | `health()` | none

### Request

API Endpoint | Function | Parameters
-- | -- | --
`POST /request/{match_id}` | `request_match($match_id)` | `$match_id = {match_id}` - Submit a new parse request.
`GET /request/{jobid}` | `request_status($job_id)` | `$job_id (string) = {jobid}` - The job ID to query.

### Heroes

API Endpoint | Function | Parameters
-- | -- | --
`GET /heroes` | `heroes_metadata()` | none
`GET /heroes/{hero_id}/matches` | `hero_matches($hero_id)` | `$hero_id (int) = {hero_id}`
`GET /heroes/{hero_id}/matchups` | `hero_matchups($hero_id)` | `$hero_id (int) = {hero_id}`
`GET /heroes/{hero_id}/durations` | `hero_durations($hero_id)` | `$hero_id (int) = {hero_id}`
`GET /heroes/{hero_id}/players` | `hero_players($hero_id)` | `$hero_id (int) = {hero_id}`

### Hero Stats

API Endpoint | Function | Parameters
-- | -- | --
`GET /heroStats` | `heroes_stats()` | none


### Leagues

API Endpoint | Function | Parameters
-- | -- | --
`GET /leagues` | `leagues()` | none

### Teams

API Endpoint | Function | Parameters
-- | -- | --
`GET /teams` | `teams()` | none
`GET /teams/{team_id}` | `team($team_id)` | `$team_id (int) = {team_id}`
`GET /teams/{team_id}/matches` | `team_matches($team_id)` | `$team_id (int) = {team_id}`
`GET /teams/{team_id}/players` | `team_players($team_id)` | `$team_id (int) = {team_id}`
`GET /teams/{team_id}/heroes` | `team_heroes($team_id)` | `$team_id (int) = {team_id}`

### Replays 

API Endpoint | Function | Parameters
-- | -- | --
`GET /replays` | `replay($match_id)` | `$match_id (int/array) = match_id` - Match IDs

### Records

API Endpoint | Function | Parameters
-- | -- | --
`GET /records/{field}` | `records($field)` | `$field (string) = {field}` - Field name to query

### Live

API Endpoint | Function | Parameters
-- | -- | --
`GET /live` | `live()` | none

### Scenarios

API Endpoint | Function | Parameters
-- | -- | --
`GET /scenarios/itemTimings` | `scenarios_item_timings()` | `$item (string) = item` - Filter by item name e.g. "spirit_vessel"; `$hero_id (int) = hero_id` - Hero ID
`GET /scenarios/laneRoles` | `scenarios_lane_roles()` | `$lane_role (string) = lane_role` - Filter by lane role 1-4 (Safe, Mid, Off, Jungle); `$hero_id (int) = hero_id` - Hero ID
`GET /scenarios/misc` | `scenarios_misc()` | `$scenario (string) = scenario` - pos_chat_1min,neg_chat_1min,courier_kill,first_blood

### Schema

API Endpoint | Function | Parameters
-- | -- | --
`GET /schema` | `schema()` | none

### Admin

API Endpoint | Function | Parameters
-- | -- | --
`GET /admin/apiMetrics` | `api_metrics()` | none
