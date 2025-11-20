<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\utils;

final class QueryStmts {

        // PLAYER QUERY
        public const PLAYERS_INIT   = "players.initialize";
        public const PLAYERS_SELECT = "players.select";
        public const PLAYERS_CREATE = "players.create";
        public const PLAYERS_UPDATE = "players.update";
        public const PLAYERS_DELETE = "players.delete";

        // WARPS QUERY 
        public const WARPS_INIT   = "warps.initialize";
        public const WARPS_SELECT = "warps.select";
        public const WARPS_CREATE = "warps.create";
        public const WARPS_UPDATE = "warps.update";
        public const WARPS_DELETE = "warps.delete";

        // FACTIONS QUERY
        public const FACTIONS_INIT   = "factions.initialize";
        public const FACTIONS_SELECT = "factions.select";
        public const FACTIONS_CREATE = "factions.create";
        public const FACTIONS_UPDATE = "factions.update";
        public const FACTIONS_DELETE = "factions.delete";

        // CLAIMS QUERY
        public const CLAIMS_INIT   = "claims.initialize";
        public const CLAIMS_SELECT = "claims.select";
        public const CLAIMS_CREATE = "claims.create";
        public const CLAIMS_UPDATE = "claims.update";
        public const CLAIMS_DELETE = "claims.delete";

        // LOGS QUERY
        public const LOGS_INIT     = "logs.initialize";
        public const LOGS_LOADALL  = "logs.loadall";
        public const LOGS_COUNTALL = "logs.countall";
        public const LOGS_COUNT    = "logs.count";
        public const LOGS_LOAD     = "logs.load";
        public const LOGS_CREATE   = "logs.create";
        public const LOGS_DELETE   = "logs.delete";

}