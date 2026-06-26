-- 校舎テーブル
CREATE TABLE IF NOT EXISTS schools (
  id   INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 校舎初期データ（実際の校舎名に変更すること）
INSERT INTO schools (name) VALUES
  ('駅前校'),
  ('本校'),
  ('北校');

-- 備品テーブル
CREATE TABLE IF NOT EXISTS items (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  school_id    INT NOT NULL,
  code         VARCHAR(50)  NOT NULL DEFAULT '',
  category     VARCHAR(10)  NOT NULL DEFAULT '',
  name         VARCHAR(100) NOT NULL DEFAULT '',
  maker        VARCHAR(100) NOT NULL DEFAULT '',
  serial       VARCHAR(100) NOT NULL DEFAULT '',
  purchased_at DATE         NULL,
  location     VARCHAR(100) NOT NULL DEFAULT '',
  set_count    INT          NOT NULL DEFAULT 0,
  is_disposed  BOOLEAN      NOT NULL DEFAULT FALSE,
  checked      BOOLEAN      NOT NULL DEFAULT FALSE,
  checked_at   DATETIME     NULL,
  checked_by   VARCHAR(100) NOT NULL DEFAULT '',
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 移動履歴テーブル
CREATE TABLE IF NOT EXISTS move_logs (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  item_id         INT          NOT NULL,
  from_school_id  INT          NOT NULL,
  from_location   VARCHAR(100) NOT NULL DEFAULT '',
  to_school_id    INT          NOT NULL,
  to_location     VARCHAR(100) NOT NULL DEFAULT '',
  moved_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  moved_by        VARCHAR(100) NOT NULL DEFAULT '',
  note            TEXT         NULL,
  FOREIGN KEY (item_id)        REFERENCES items(id),
  FOREIGN KEY (from_school_id) REFERENCES schools(id),
  FOREIGN KEY (to_school_id)   REFERENCES schools(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 操作ログテーブル
CREATE TABLE IF NOT EXISTS operation_logs (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  item_id     INT          NULL,
  action      VARCHAR(50)  NOT NULL DEFAULT '',
  operator    VARCHAR(100) NOT NULL DEFAULT '',
  operated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  detail      TEXT         NULL,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
