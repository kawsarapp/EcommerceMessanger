---
description: How to deploy website updates to Linux VPS
---
1. Connect via SSH to your server.
2. Navigate to your project directory. `cd ~/htdocs/asianhost.net/EcommerceMessanger`
3. Pull the latest code. `git pull origin main`
4. Update composer packages (if needed): `composer install --optimize-autoloader --no-dev`
5. Migrate database: `php artisan migrate --force`
6. Go to the new WhatsApp bot directory: `cd whatsapp-bot`
7. Install node packages: `npm install`
8. Make sure permissions are correct `chmod +x start_vps.sh` (run this from your main project folder!)
9. You can now use PM2 to manage both Horizon and WhatsApp with the new config.
10. Start everything (from main dir!): `./start_vps.sh` (or manually run `pm2 start ecosystem.config.json`)

To check logs:
`pm2 logs`
To stop:
`pm2 stop all`
