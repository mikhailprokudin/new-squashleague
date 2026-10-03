SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS squash_league
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE squash_league;

DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS players;
DROP TABLE IF EXISTS teams;

CREATE TABLE teams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE players (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  team_id INT UNSIGNED NOT NULL,
  division ENUM('red', 'yellow') NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_players_team FOREIGN KEY (team_id) REFERENCES teams(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  player1_id INT UNSIGNED NOT NULL,
  player2_id INT UNSIGNED NOT NULL,
  winner_id INT UNSIGNED NOT NULL,
  score ENUM('3-0', '3-1', '3-2') NOT NULL,
  points_player1 DECIMAL(4,1) NOT NULL,
  points_player2 DECIMAL(4,1) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_matches_player1 FOREIGN KEY (player1_id) REFERENCES players(id),
  CONSTRAINT fk_matches_player2 FOREIGN KEY (player2_id) REFERENCES players(id),
  CONSTRAINT fk_matches_winner FOREIGN KEY (winner_id) REFERENCES players(id),
  INDEX idx_matches_pair (player1_id, player2_id),
  INDEX idx_matches_pair_rev (player2_id, player1_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO teams (name, slug) VALUES
  ('Команда A', 'team-a'),
  ('Команда B', 'team-b'),
  ('Команда C', 'team-c');

-- Columns left→right = teams A/B/C; top block = red, bottom block = yellow
INSERT INTO players (team_id, division, name) VALUES
  -- Team A red
  (1, 'red', 'Антропов Глеб'),
  (1, 'red', 'Зоткин Леха'),
  (1, 'red', 'Макаров'),
  (1, 'red', 'Ущалов Амир'),
  (1, 'red', 'Шалин «уже лысый» Илья'),
  (1, 'red', 'Ли Вадик'),
  -- Team A yellow
  (1, 'yellow', 'Любимый тренер'),
  (1, 'yellow', 'Белозерова Юлия'),
  (1, 'yellow', 'Лепа Ирина'),
  (1, 'yellow', 'Злодеева Алена'),
  (1, 'yellow', 'Гончарова Дарья'),
  (1, 'yellow', 'Моесеенко Марина'),
  (1, 'yellow', 'Анастасия Силина'),

  -- Team B red
  (2, 'red', 'Горелов Юра'),
  (2, 'red', 'Токарева Леночка'),
  (2, 'red', 'Трибунский Саня'),
  (2, 'red', 'Калинин Илья'),
  (2, 'red', 'Самочернов Игорь'),
  (2, 'red', 'Никита Дикий Лев Драньков'),
  -- Team B yellow
  (2, 'yellow', 'Антропов Гриша'),
  (2, 'yellow', 'Гутова Таня'),
  (2, 'yellow', 'Мокеева Елена'),
  (2, 'yellow', 'Чайко Дима'),
  (2, 'yellow', 'Мотовилова Василина'),
  (2, 'yellow', 'Лазарев Константин'),
  (2, 'yellow', 'Фунтикова Ирина'),

  -- Team C red
  (3, 'red', 'Шварев Леха'),
  (3, 'red', 'Пермяков Вован'),
  (3, 'red', 'Калинин Витя'),
  (3, 'red', 'Моесеенко Стёпка'),
  (3, 'red', 'Прокудин Миша'),
  (3, 'red', 'Дроздов Гошан'),
  -- Team C yellow
  (3, 'yellow', 'Плуталов Андрей'),
  (3, 'yellow', 'Чайко Миша'),
  (3, 'yellow', 'Бровкин Александр'),
  (3, 'yellow', 'Ленденёва Женя'),
  (3, 'yellow', 'Алёна Дьякова'),
  (3, 'yellow', 'Левчук Любовь'),
  (3, 'yellow', 'Екатерина Миронова');
