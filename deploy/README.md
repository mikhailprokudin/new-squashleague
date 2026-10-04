# Deploy (Docker + host nginx)

## GitHub Actions (автодеплой)

При пуше в `main` workflow [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) по SSH заходит на сервер, делает `git pull` и `docker compose ... up --build -d`.

### Secrets в GitHub

Repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Пример | Обязательно |
|--------|--------|-------------|
| `SSH_HOST` | `203.0.113.10` или `squashleague.ru` | да |
| `SSH_USER` | `deploy` | да |
| `SSH_PRIVATE_KEY` | содержимое приватного ключа (`-----BEGIN ...`) | да |
| `SSH_PORT` | `22` | нет (по умолчанию 22) |
| `DEPLOY_PATH` | `/opt/new-league` | нет (по умолчанию `/opt/new-league`) |

### Один раз на сервере

1. Пользователь для деплоя (с доступом к Docker):

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG docker deploy
sudo mkdir -p /home/deploy/.ssh
# публичный ключ, парный к SSH_PRIVATE_KEY из GitHub Secrets:
sudo nano /home/deploy/.ssh/authorized_keys
sudo chown -R deploy:deploy /home/deploy/.ssh
sudo chmod 700 /home/deploy/.ssh
sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

2. Deploy key для `git clone` / `git fetch` репозитория (отдельный SSH-ключ **на сервере**):

```bash
sudo -u deploy ssh-keygen -t ed25519 -f /home/deploy/.ssh/id_ed25519 -N ""
sudo -u deploy cat /home/deploy/.ssh/id_ed25519.pub
```

Добавь этот **публичный** ключ в GitHub: repo → **Settings → Deploy keys → Add deploy key** (Read-only достаточно).

Проверка с сервера:

```bash
sudo -u deploy ssh -T git@github.com
```

3. Каталог и `.env` (создаётся один раз вручную, в git не попадает):

```bash
sudo mkdir -p /opt/new-league
sudo chown deploy:deploy /opt/new-league
# после первого успешного clone workflow'ом — или сразу:
sudo -u deploy git clone git@github.com:mikhailprokudin/new-squashleague.git /opt/new-league
cd /opt/new-league
sudo -u deploy cp .env.example .env
sudo -u deploy nano .env
```

4. Nginx на хосте — как ниже (секция 5).

### Ручной запуск

GitHub → **Actions → Deploy → Run workflow**.

---

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

Команда должна выполняться **после** того, как репозиторий уже лежит в `/opt/new-league` (иначе файла не будет). Используй абсолютный путь:

```bash
# убедись, что проект на месте:
ls /opt/new-league/deploy/nginx.conf

sudo cp /opt/new-league/deploy/nginx.conf /etc/nginx/sites-available/squash-league
sudo ln -sf /etc/nginx/sites-available/squash-league /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

HTTPS:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d squashleague.ru -d www.squashleague.ru
```

App URL: `https://squashleague.ru`  
Entry form: `https://squashleague.ru/entry`
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
