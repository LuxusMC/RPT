
# RPT Virion for PocketMine-MP

**RPT (Ranked Progression Tracker)** is a virion for [PocketMine-MP](https://pmmp.io/) servers, providing a competitive ranked progression system for Minecraft Bedrock. It tracks players' rank progress based on their match performance, with features like placement games, rank protection, and dynamic RP calculations for different match formats.

## Features

- **Ranked Progression**: Tracks players’ ranks through dynamic **RP (Rank Points)**.
- **Placement Games**: Determines initial rank after placement matches.
- **Rank Protection**: Protects players from deranking too quickly.
- **Flexible Match Formats**: Supports team-based and free-for-all match types.
- **Configurable**: Easily adjust rank thresholds and other mechanics.

## Installation

### Using Poggit

To include `RPT` as a virion in your plugin:

1. Add this to your `.poggit.yml`:

   ```yaml
   libs:
     - src: LuxusMC/RPT
       version: ^1.0.0
   ```

2. Build the plugin on [Poggit](https://poggit.pmmp.io/ci).

### Manual Installation

1. Download `RPT.phar` and place it in your plugin's `libs` directory.
2. Add it to your `plugin.yml` if required.

## Usage

### Basic Integration

To use `RPT` in your plugin, instantiate the `RPT` class with your `PluginBase` instance and retrieve or set ranks as needed.

#### Example Code

Here’s an example of how to calculate match RP with the `calculateMatchRP` method.

```php
use rpt\RPT;

class MyPlugin extends PluginBase {
    private RPT $rpt;

    public function onEnable(): void {
        $this->rpt = new RPT($this);
    }

    /**
     * Example match RP calculation for a 1v1 match.
     */
    public function calculateMatchRPExample(): void {
        $teams = [
            [$player1],  // Team 1
            [$player2],  // Team 2
        ];
        $results = [
            1,  // Team 1 wins
            0   // Team 2
        ];
        // Calculate RP based on match results
        $this->rpt->calculateMatchRP($teams, $results);
    }

    /**
     * Example match RP calculation for a 3v3v3 match.
     */
    public function calculateMatchRPExample3v3v3(): void {
        $teams = [
            [$player1, $player2, $player3],  // Team 1
            [$player4, $player5, $player6],  // Team 2
            [$player7, $player8, $player9],  // Team 3
        ];
        $results = [
            3,  // Team 1 wins
            1,  // Team 2
            0   // Team 3
        ];
        // Calculate RP based on match results
        $this->rpt->calculateMatchRP($teams, $results);
    }

    /**
     * Example match RP calculation for a Free For All match.
     */
    public function calculateMatchRPExampleFFA(): void {
        $players = [
            [$player1],
            [$player2],
            [$player3],
            [$player4],
            [$player5]
        ];
        $results = [
            1,  // Player 1
            0,  // Player 2
            9,  // Player 3 wins
            3,  // Player 4
            5   // Player 5
        ];
        // Calculate RP based on match results
        $this->rpt->calculateMatchRP($players, $results);
    }
}
```

### Available Methods

- **`getPlayerRP(string $player)`**: Get the RP of a player.
- **`setPlayerRP(string $player, int $rp)`**: Set a player's RP.
- **`getRank(string $player)`**: Get the current rank of a player.

## Contributing

To contribute:

1. Fork the repository.
2. Create a branch and make your changes.
3. Submit a pull request.

## License

This virion is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
