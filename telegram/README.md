# Telegram Bot Demo

A Telegram bot that creates a new trial user in the reseller account whenever someone presses **Request test account**. The bot sends the new account's login, password, tariff, and trial dates back to that Telegram chat.

## Requirements

- PHP with the cURL extension
- A Telegram bot token from BotFather
- A reseller API token

## Run

Set `TELEGRAM_BOT_TOKEN` and `RESELLER_API_TOKEN` at the top of `bot.php`, then start the bot:

```bash
php bot.php
```

Open the bot in Telegram, send `/start`, then press **Request test account**. Each press creates a new user. Keep the process running to receive button presses.

The reseller API client is loaded from `../api.php`. The API timeout is 90 seconds. Trial dates are formatted using the PHP process timezone.
