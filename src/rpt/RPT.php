<?php

declare(strict_types=1);

namespace rpt;

use InvalidArgumentException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use function mkdir;

/**
 * RPT (Ranked Progression Tracker) main class where players' RP (Rank Points) are handled and process.
 */
final class RPT {

	private Config $config;

	private Config $players;

	/** @phpstan-var array<string, Rank> */
	private array $ranks = [];

	/**
	 * Initializes the configuration and player data, and sets up the ranks.
	 *
	 * @phpstan-param PluginBase $plugin The plugin instance.
	 */
	public function __construct(private readonly PluginBase $plugin) {
		@mkdir($this->plugin->getDataFolder() . "rpt");
		$this->config = new Config($this->plugin->getDataFolder() . "rpt/config.json", Config::JSON, [
			"placementGames" => 3,
			"ranks" => [
				"Bronze" => [
					"minRP" => 0,
					"format" => "§7Bronze"
				],
				"Silver" => [
					"minRP" => 500,
					"format" => "§fSilver"
				],
				"Gold" => [
					"minRP" => 1000,
					"format" => "§6Gold"
				],
				"Platinum" => [
					"minRP" => 1500,
					"format" => "§bPlatinum"
				],
				"Diamond" => [
					"minRP" => 2000,
					"format" => "§3Diamond"
				],
				"Master" => [
					"minRP" => 2500,
					"format" => "§dMaster"
				],
				"Grandmaster" => [
					"minRP" => 3000,
					"format" => "§5Grandmaster"
				]
			]
		]);

		$this->ranks["Unranked"] = new Rank("Unranked", "§7Unranked", 0);
		foreach ($this->config->get("ranks", []) as $name => $data) {
			$this->ranks[$name] = new Rank($name, $data["format"], $data["minRP"]);
		}

		$this->players = new Config($this->plugin->getDataFolder() . "rpt/players.json", Config::JSON, [
			"Steve" => [
				"rp" => 0,
				"placementGames" => $this->config->get("placementGames"),
				"protected" => false
			]
		]);
	}

	/**
	 * **THIS ACTION CANNOT BE UNDONE**
	 *
	 * Reset all players' RP and ranks to the default values.
	 * This is useful for testing purposes, or if you want to reset the entire system.
	 * You should be careful when using this method as it will reset all players' RP and ranks.
	 * You may want to notify players or log this action.
	 */
	public function resetAllRP(): void {
		foreach ($this->players->getAll() as $playerName => $data) {
			$this->players->setNested($playerName . ".rp", 0);
			$this->players->setNested($playerName . ".placementGames", $this->config->get("placementGames"));
			$this->players->setNested($playerName . ".protected", false);
		}

		$this->players->save();
	}

	/**
	 * @phpstan-param string $player The name of the player.
	 * @phpstan-return int The RP (Rank Points) of the player.
	 */
	public function getPlayerRP(string $player): int {
		return $this->players->getNested($player . ".rp", 0);
	}

	/**
	 * @phpstan-param string $player The name of the player.
	 * @phpstan-param int $rp The new RP (Rank Points) value.
	 */
	public function setPlayerRP(string $player, int $rp): void {
		$this->players->setNested($player . ".rp", $rp);
		$this->players->save();
	}

	/**
	 * @phpstan-param string $player The name of the player.
	 * @phpstan-return Rank The rank of the player.
	 */
	public function getRank(string $player): Rank {
		$rank = $this->ranks["Unranked"];
		if ($this->players->getNested($player . ".placementGames", $this->config->get("placementGames")) > 0) {
			return $rank;
		}

		$rp = $this->getPlayerRP($player);
		foreach ($this->ranks as $r) {
			if ($rp >= $r->minRP) {
				$rank = $r;
			}
		}
		return $rank;
	}

	/**
	 * @phpstan-return array<string, Rank>
	 */
	public function getRanks(): array {
		return $this->ranks;
	}

	/**
	 * @return Config All the players' RP (Rank Points) data, including their ranks, placement games, and protection status.
	 */
	public function getPlayers(): Config {
		return $this->players;
	}

	/**
	 * Calculate the RP changes for a match.
	 *
	 * @phpstan-param array<array<Player>> $teams
	 * @phpstan-param array<int> $results Results can be score/kill count, etc. depending on the match type. If its win or loss, use 1 for winning team/player and 0 for every other team/player.
	 * <pre>
	 * Example #1 (1v1 match):
	 *
	 * calculateMatchRP(
	 *   $teams = [
	 *     [$Player1],
	 *     [$Player2],
	 *   ],
	 *   $results = [
	 *     1,
	 *     0
	 *   ]
	 * );
	 * </pre>
	 * <pre>
	 * Example #2 (3v3v3 match):
	 *
	 * calculateMatchRP(
	 *   $teams = [
	 *     [$Player1, $Player2, $Player3],
	 *     [$Player4, $Player5, $Player6],
	 *     [$Player7, $Player8, $Player9],
	 *   ],
	 *   $results = [
	 *     3,
	 *     1,
	 *     0
	 *  ]
	 * );
	 * </pre>
	 * <pre>
	 * Example #3 (Free For All match):
	 *
	 * calculateMatchRP(
	 *   $teams = [
	 *     $Player1,
	 *     $Player2,
	 *     $Player3,
	 *     $Player4,
	 *     $Player5
	 *   ],
	 *   $results = [
	 *     1,
	 *     0,
	 *     9,
	 *     3,
	 *     5
	 *   ]
	 * );
	 * </pre>
	 *
	 * @phpstan-throws InvalidArgumentException if the size of teams and results arrays do not match.
	 */
	public function calculateMatchRP(array $teams, array $results): void {
		if (count($teams) !== count($results)) {
			throw new InvalidArgumentException("Teams and results array must have the same size.");
		}

		$maxResult = max($results);
		$normalizedResults = array_map(fn ($result) => $result / $maxResult, $results);
		foreach ($teams as $index => $team) {
			$teamRP = 0;
			foreach ($team as $player) {
				$playerName = $player->getName();
				$teamRP += $this->getPlayerRP($playerName);
			}

			$teamRP = count($team) > 0 ? $teamRP / count($team) : 0;
			foreach ($teams as $otherIndex => $otherTeam) {
				if ($index === $otherIndex) {
					continue;
				}

				$otherTeamRP = 0;
				foreach ($otherTeam as $otherPlayer) {
					$otherPlayerName = $otherPlayer->getName();
					$otherTeamRP += $this->getPlayerRP($otherPlayerName);
				}

				$otherTeamRP = count($otherTeam) > 0 ? $otherTeamRP / count($otherTeam) : 0;

				$expected = 1 / (1 + pow(10, ($otherTeamRP - $teamRP) / 400));
				$actual = $normalizedResults[$index] > $normalizedResults[$otherIndex] ? 1 : ($normalizedResults[$index] === $normalizedResults[$otherIndex] ? 0.5 : 0);
				$rpChange = 32 * ($actual - $expected);

				foreach ($team as $player) {
					$playerName = $player->getName();
					$currentRP = $this->getPlayerRP($playerName);
					$newRP = max(0, round($currentRP + $rpChange));
					$currentRank = $this->getRank($playerName);

					$minRP = $currentRank->minRP;
					$isProtected = $this->players->getNested($playerName . ".protected", false);
					if ($newRP < $minRP) {
						if ($isProtected) {
							$this->setPlayerRP($playerName, $newRP);
							$this->players->setNested($playerName . ".protected", false);
						} else {
							$this->setPlayerRP($playerName, $minRP);
							$this->players->setNested($playerName . ".protected", true);
						}
					} else {
						$this->setPlayerRP($playerName, $newRP);
					}
				}
			}
		}

		foreach ($teams as $team) {
			foreach ($team as $player) {
				$playerName = $player->getName();
				$placementGames = $this->players->getNested($playerName . ".placementGames", 0);

				if ($placementGames > 0) {
					$this->players->setNested($playerName . ".placementGames", $placementGames - 1);
				}
			}
		}

		$this->players->save();
	}
}