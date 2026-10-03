# Deploy (Docker + host nginx)

## 1. Server prerequisites

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 nginx
sudo usermod -aG docker $USER
# re-login after usermod
```

## 2. Upload project

```bash
rsync -avz --exclude front/node_modules --exclude front/dist \
  ./ user@SERVER:/opt/new-league/
```

## 3. Configure secrets

```bash
cd /opt/new-league
cp .env.example .env
nano .env   # set strong MYSQL_ROOT_PASSWORD and DB_PASSWORD (same value if DB_USER=root)
```

## 4. Start containers

```bash
docker compose -f docker-compose.prod.yml --env-file .env up --build -d
```

Check locally on the server:

```bash
curl -I http://127.0.0.1:3000/
curl http://127.0.0.1:3000/api/standings
```

MySQL and API are **not** exposed to the internet.

## 5. Host nginx

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/squash-league
sudo nano /etc/nginx/sites-available/squash-league   # set your domain
sudo ln -sf /etc/nginx/sites-available/squash-league /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

HTTPS:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d league.example.com
```

App URL: `https://league.example.com`  
Entry form: `https://league.example.com/entry`

## 6. Update

```bash
cd /opt/new-league
# git pull  or  rsync
docker compose -f docker-compose.prod.yml --env-file .env up --build -d
```

## 7. Backup / restore

```bash
# backup
docker compose -f docker-compose.prod.yml --env-file .env exec db \
  mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --default-character-set=utf8mb4 squash_league \
  > backup-$(date +%F).sql

# restore
docker compose -f docker-compose.prod.yml --env-file .env exec -T db \
  mysql -uroot -p"$MYSQL_ROOT_PASSWORD" --default-character-set=utf8mb4 squash_league \
  < backup-YYYY-MM-DD.sql
```

Or load password from `.env`:

```bash
set -a && source .env && set +a
```
