ALTER DATABASE fotbaltesty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE users
  MODIFY email VARCHAR(240) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  MODIFY password VARCHAR(255) NULL,
  MODIFY activationDate TIMESTAMP NULL DEFAULT NULL,
  MODIFY deleteDate TIMESTAMP NULL DEFAULT NULL,
  MODIFY lastSignInDate TIMESTAMP NULL DEFAULT NULL,
  MODIFY token VARCHAR(64) NULL;

ALTER TABLE questions
  MODIFY text VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY updateDate TIMESTAMP NULL DEFAULT NULL,
  MODIFY deactivateDate TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE tests
  MODIFY startDate TIMESTAMP NULL DEFAULT NULL,
  MODIFY endDate TIMESTAMP NULL DEFAULT NULL;

CREATE INDEX idx_questions_active_created ON questions (deactivated, createDate);
CREATE INDEX idx_tests_user_end ON tests (user_id, endDate);
CREATE INDEX idx_results_test ON results (tests_id);
