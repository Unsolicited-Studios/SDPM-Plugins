# PlayerTrade
![PlayerTrade](https://media.discordapp.net/attachments/973097510748966982/981141129560981504/PlayerTrade.gif?width=1416&height=503)

A PocketMine-MP plugin that implements trade like PC server! Lots of improvements from the original.

## Features
* Clear design
* User-modifiable message
* Trade request expiration time can be set
* Double confirmation trade

## Commands
| Command     | Description | Permission  |
| ----------- | ----------- | ----------- |
| /trade request <player> | Request a trade from the player. | playertrade.command |
| /trade accept <player> | Accept the player's trade request. | playertrade.command |
| /trade deny <player> | Decline the player's trade request. | playertrade.command |

## Permissions
| Permission  | Default     |
| ----------- | ----------- |
| playertrade.command | true |

## Changes compared to the original
* Added a double confirmation trade system
* Fixed item duplication on complete (Plugins-PocketMineMP/PlayerTrade#2 & Plugins-PocketMineMP/PlayerTrade#3)
* Fixed rare occurrence of items not taken away from sender on complete
* Fixed duplication on close due to mismatched inventory
* Fixed synchronization problems causing inconsistency