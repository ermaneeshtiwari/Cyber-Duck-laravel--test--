# LOCAL DEVELOPMENT
## Requirements:
Note: This local setup was aimed at macOS, if you need to tweak it feel free to just point us to the changed configuration.

1. Have PHP installed on version 8.2 and composer 2
2. You can use `php artisan serve` but feel free to setup any local setup: Sail, valet or any local solution (MAMP).
3. Have node installed in the latest version
   1. We suggest [n package](https://www.npmjs.com/package/n) to manage the node version (`npm install -g n && n 21`)

## Commands to setup
We did not automate it to keep it generic and suggest:

From the root of the project:
```bash
composer install 
cp .env.example .env
php artisan key:generate
sudo n 21
npm install && npm run dev
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

## Database access
The default is using sqlite lite, so feel free to connect to it
